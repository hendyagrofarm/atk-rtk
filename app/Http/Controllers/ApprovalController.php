<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Request as Pengajuan;
use App\Support\ApprovalChain;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ApprovalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $pendingRequests = Pengajuan::query()
            ->with([
                'user',
                'entity',
                'warehouse',
                'details.item',
            ])
            ->with('approvals')
            ->where('status', 'pending')
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $this->scopeToUserAccess($query, $user, $allowedEntityIds, $allowedWarehouseIds)
            )
            ->latest()
            ->get();

        $approvalHistories = Pengajuan::query()
            ->with([
                'user',
                'entity',
                'warehouse',
                'approver',
                'details.item',
            ])
            ->whereIn('status', [
                'approved',
                'rejected',
            ])
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $this->scopeToUserAccess($query, $user, $allowedEntityIds, $allowedWarehouseIds)
            )
            ->latest('updated_at')
            ->get();

        return view(
            'approvals.index',
            compact('pendingRequests', 'approvalHistories')
        );
    }

    public function show(Pengajuan $approval)
    {
        $this->ensureApprovalAccess($approval);

        $approval->load([
            'user.position',
            'entity',
            'warehouse',
            'approver',
            'approvals.user',
            'details.item.category',
            'details.item.itemStocks' => function ($query) use ($approval) {
                $query
                    ->where('entity_id', $approval->entity_id)
                    ->where('warehouse_id', $approval->warehouse_id)
                    ->with([
                        'entity',
                        'warehouse',
                    ]);
            },
        ]);

        $currentStep = ApprovalChain::currentStep($approval);
        $canAct = $approval->usesApprovalChain()
            ? ApprovalChain::canAct(Auth::user(), $approval)
            : $approval->status === 'pending';
        $isFinalStep = $currentStep
            ? ApprovalChain::isFinalStep($approval, $currentStep)
            : false;

        return view(
            'approvals.show',
            compact('approval', 'currentStep', 'canAct', 'isFinalStep')
        );
    }

    public function approve(
        HttpRequest $request,
        Pengajuan $approval
    ) {
        $this->ensureApprovalAccess($approval);

        if ($approval->usesApprovalChain()) {
            return $this->approveChain($request, $approval);
        }

        $request->validate([
            'approved_quantity' => [
                'required',
                'array',
            ],
            'approved_quantity.*' => [
                'required',
                'integer',
                'min:0',
            ],
            'approver_note' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        try {
            DB::transaction(function () use ($request, $approval) {
                $lockedApproval = Pengajuan::query()
                    ->where('id', $approval->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureApprovalAccess($lockedApproval);

                if ($lockedApproval->status !== 'pending') {
                    throw new \RuntimeException(
                        'Pengajuan ini sudah diproses sebelumnya.'
                    );
                }

                if (
                    ! $lockedApproval->entity_id
                    || ! $lockedApproval->warehouse_id
                ) {
                    throw new \RuntimeException(
                        'Pengajuan belum memiliki entitas atau gudang tujuan.'
                    );
                }

                $lockedApproval->load([
                    'entity',
                    'warehouse',
                    'details',
                ]);

                foreach ($lockedApproval->details as $detail) {
                    $approvedQuantity = (int) (
                        $request->approved_quantity[$detail->id] ?? 0
                    );

                    if ($approvedQuantity > $detail->quantity) {
                        throw new \RuntimeException(
                            'Jumlah disetujui tidak boleh lebih dari jumlah diminta.'
                        );
                    }

                    if (
                        $lockedApproval->request_type === 'custom'
                        || is_null($detail->item_id)
                    ) {
                        $detail->update([
                            'approved_quantity' => $approvedQuantity,
                        ]);

                        continue;
                    }

                    // Tidak ada yang disetujui: tidak perlu memeriksa atau mengubah stok.
                    if ($approvedQuantity === 0) {
                        $detail->update([
                            'approved_quantity' => 0,
                        ]);

                        continue;
                    }

                    $item = Item::query()
                        ->where('id', $detail->item_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $itemStock = ItemStock::query()
                        ->where('entity_id', $lockedApproval->entity_id)
                        ->where('warehouse_id', $lockedApproval->warehouse_id)
                        ->where('item_id', $detail->item_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $itemStock) {
                        throw new \RuntimeException(
                            'Stok barang "' . $item->name
                            . '" belum tersedia pada '
                            . ($lockedApproval->entity?->code ?? '-')
                            . ' / '
                            . ($lockedApproval->warehouse?->name ?? '-')
                            . '.'
                        );
                    }

                    if ($itemStock->current_stock < $approvedQuantity) {
                        throw new \RuntimeException(
                            'Stok barang "' . $item->name
                            . '" pada '
                            . ($lockedApproval->entity?->code ?? '-')
                            . ' / '
                            . ($lockedApproval->warehouse?->name ?? '-')
                            . ' tidak cukup. Stok saat ini: '
                            . $itemStock->current_stock
                        );
                    }

                    $detail->update([
                        'approved_quantity' => $approvedQuantity,
                    ]);

                    if ($approvedQuantity > 0) {
                        $itemStock->decrement(
                            'current_stock',
                            $approvedQuantity
                        );
                    }

                    $item->current_stock = $item
                        ->itemStocks()
                        ->sum('current_stock');

                    $item->save();
                }

                $lockedApproval->update([
                    'status' => 'approved',
                    'approver_id' => Auth::id(),
                    'approver_note' => $request->approver_note,
                    'approved_at' => now(),
                ]);
            });

            $message = $approval->request_type === 'custom'
                ? 'Pengajuan custom berhasil disetujui. Stok tidak berubah.'
                : 'Pengajuan berhasil disetujui dan stok lokasi sudah dikurangi.';

            return redirect()
                ->route('approvals.index')
                ->with('success', $message);
        } catch (HttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memproses pengajuan. Silakan coba kembali.');
        }
    }

    public function reject(
        HttpRequest $request,
        Pengajuan $approval
    ) {
        $this->ensureApprovalAccess($approval);

        $isChain = $approval->usesApprovalChain();

        $request->validate([
            'rejection_reason' => [
                'required',
                Rule::in(array_keys(Pengajuan::REJECTION_REASONS)),
            ],
            'approver_note' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib dipilih.',
            'rejection_reason.in' => 'Alasan penolakan tidak valid.',
        ]);

        if ($isChain) {
            try {
                ApprovalChain::reject(
                    $approval,
                    Auth::user(),
                    $request->rejection_reason,
                    $request->approver_note
                );
            } catch (RuntimeException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            return redirect()
                ->route('approvals.index')
                ->with('success', 'Pengajuan berhasil ditolak.');
        }

        try {
            DB::transaction(function () use ($request, $approval) {
                $lockedApproval = Pengajuan::query()
                    ->where('id', $approval->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureApprovalAccess($lockedApproval);

                if ($lockedApproval->status !== 'pending') {
                    throw new \RuntimeException(
                        'Pengajuan ini sudah diproses sebelumnya.'
                    );
                }

                $lockedApproval->update([
                    'status' => 'rejected',
                    'approver_id' => Auth::id(),
                    'approver_note' => $request->approver_note,
                    'rejection_reason' => $request->rejection_reason,
                    'approved_at' => now(),
                ]);
            });

            return redirect()
                ->route('approvals.index')
                ->with(
                    'success',
                    'Pengajuan berhasil ditolak. Stok tidak berubah.'
                );
        } catch (HttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memproses pengajuan. Silakan coba kembali.');
        }
    }

    /**
     * Approval pengajuan custom dengan alur bertingkat.
     */
    private function approveChain(HttpRequest $request, Pengajuan $approval)
    {
        $request->validate([
            'approved_quantity' => ['nullable', 'array'],
            'approved_quantity.*' => ['nullable', 'integer', 'min:0'],
            'approver_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $step = ApprovalChain::currentStep($approval);

        try {
            $completed = ApprovalChain::approve(
                $approval,
                Auth::user(),
                $request->approver_note,
                $request->input('approved_quantity', [])
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($completed) {
            $message = 'Seluruh tahap approval selesai. Pengajuan disetujui.';
        } else {
            $next = ApprovalChain::currentStep($approval->fresh('approvals'));

            $message = ($step?->label ?? 'Tahap') . ' ' . ($step?->position_name ?? '')
                . ' tercatat. Pengajuan diteruskan ke ' . ($next?->position_name ?? 'tahap berikutnya') . '.';
        }

        return redirect()
            ->route('approvals.index')
            ->with('success', $message);
    }

    /**
     * Batasi daftar pengajuan sesuai akses lokasi user, ditambah pengajuan
     * custom yang tahapnya memang menjadi jabatan user.
     */
    private function scopeToUserAccess($query, $user, $allowedEntityIds, $allowedWarehouseIds)
    {
        return $query->where(function ($scope) use ($user, $allowedEntityIds, $allowedWarehouseIds) {
            $scope->where(function ($location) use ($allowedEntityIds, $allowedWarehouseIds) {
                $location
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds);
            });

            if ($user->position_id) {
                $scope->orWhere(function ($chain) use ($user, $allowedEntityIds) {
                    $chain
                        ->whereIn('entity_id', $allowedEntityIds)
                        ->whereHas(
                            'approvals',
                            fn ($approvals) => $approvals->where('position_id', $user->position_id)
                        );
                });
            }
        });
    }

    private function ensureApprovalAccess(Pengajuan $approval): void
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($approval->usesApprovalChain()) {
            if (! ApprovalChain::canView($user, $approval)) {
                abort(403, 'Anda tidak memiliki akses untuk pengajuan ini.');
            }

            return;
        }

        if (
            ! $approval->entity_id
            || ! $approval->warehouse_id
            || ! $user->canAccessLocation(
                $approval->entity_id,
                $approval->warehouse_id
            )
        ) {
            abort(
                403,
                'Anda tidak memiliki akses untuk memproses pengajuan lokasi ini.'
            );
        }
    }
}
