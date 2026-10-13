<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Request as Pengajuan;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SymfonyComponentHttpKernelxceptionHttpException;
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
            ->where('status', 'pending')
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
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
                fn ($query) => $query
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
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
            'user',
            'entity',
            'warehouse',
            'approver',
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

        return view(
            'approvals.show',
            compact('approval')
        );
    }

    public function approve(
        HttpRequest $request,
        Pengajuan $approval
    ) {
        $this->ensureApprovalAccess($approval);

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

        $request->validate([
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

                $lockedApproval->update([
                    'status' => 'rejected',
                    'approver_id' => Auth::id(),
                    'approver_note' => $request->approver_note,
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

    private function ensureApprovalAccess(Pengajuan $approval): void
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
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
