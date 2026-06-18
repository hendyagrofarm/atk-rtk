<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Request as Pengajuan;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApprovalController extends Controller
{
    /**
     * Menampilkan daftar pengajuan pending dan riwayat approval.
     */
    public function index()
    {
        $pendingRequests = Pengajuan::with(['user', 'entity', 'details.item'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $approvalHistories = Pengajuan::with(['user', 'entity', 'approver', 'details.item'])
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('updated_at')
            ->get();

        return view('approvals.index', compact('pendingRequests', 'approvalHistories'));
    }

    /**
     * Menampilkan detail pengajuan untuk approval.
     */
    public function show(Pengajuan $approval)
    {
        $approval->load([
            'user',
            'entity',
            'approver',
            'details.item.category',
            'details.item.itemStocks.entity',
        ]);

        return view('approvals.show', compact('approval'));
    }

    /**
     * Menyetujui pengajuan.
     */
    public function approve(HttpRequest $request, Pengajuan $approval)
    {
        $request->validate([
            'approved_quantity' => ['required', 'array'],
            'approved_quantity.*' => ['required', 'integer', 'min:0'],
            'approver_note' => ['nullable', 'string'],
        ], [
            'approved_quantity.required' => 'Jumlah disetujui wajib diisi.',
            'approved_quantity.array' => 'Format jumlah disetujui tidak valid.',
            'approved_quantity.*.required' => 'Jumlah disetujui per barang wajib diisi.',
            'approved_quantity.*.integer' => 'Jumlah disetujui harus berupa angka.',
            'approved_quantity.*.min' => 'Jumlah disetujui minimal 0.',
        ]);

        try {
            DB::transaction(function () use ($request, $approval) {
                $lockedApproval = Pengajuan::where('id', $approval->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedApproval->status !== 'pending') {
                    throw new \Exception('Pengajuan ini sudah diproses sebelumnya.');
                }

                if (! $lockedApproval->entity_id) {
                    throw new \Exception('Pengajuan ini belum memiliki entitas ANR/MAA.');
                }

                $lockedApproval->load('details');

                foreach ($lockedApproval->details as $detail) {
                    $approvedQuantity = (int) ($request->approved_quantity[$detail->id] ?? 0);

                    if ($approvedQuantity > $detail->quantity) {
                        throw new \Exception('Jumlah disetujui tidak boleh lebih dari jumlah diminta.');
                    }

                    $item = Item::where('id', $detail->item_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $itemStock = ItemStock::where('entity_id', $lockedApproval->entity_id)
                        ->where('item_id', $detail->item_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $itemStock) {
                        $itemStock = ItemStock::create([
                            'entity_id' => $lockedApproval->entity_id,
                            'item_id' => $detail->item_id,
                            'current_stock' => 0,
                            'minimum_stock' => $item->minimum_stock ?? 0,
                        ]);
                    }

                    if ($itemStock->current_stock < $approvedQuantity) {
                        throw new \Exception(
                            'Stok barang "' . $item->name . '" untuk entitas ' .
                            ($lockedApproval->entity->code ?? '-') .
                            ' tidak cukup. Stok saat ini: ' . $itemStock->current_stock
                        );
                    }

                    $detail->update([
                        'approved_quantity' => $approvedQuantity,
                    ]);

                    if ($approvedQuantity > 0) {
                        $itemStock->current_stock = $itemStock->current_stock - $approvedQuantity;
                        $itemStock->save();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Sinkron stok utama items
                    |--------------------------------------------------------------------------
                    | Untuk sementara items.current_stock tetap menjadi total seluruh entitas,
                    | agar fitur lama yang masih membaca kolom ini tidak rusak.
                    */
                    $item->current_stock = $item->itemStocks()->sum('current_stock');
                    $item->save();
                }

                $lockedApproval->update([
                    'status' => 'approved',
                    'approver_id' => Auth::id(),
                    'approver_note' => $request->approver_note,
                    'approved_at' => now(),
                ]);
            });

            return redirect()
                ->route('approvals.index')
                ->with('success', 'Pengajuan berhasil disetujui dan stok entitas sudah dikurangi.');
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Menolak pengajuan.
     */
    public function reject(HttpRequest $request, Pengajuan $approval)
    {
        $request->validate([
            'approver_note' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($request, $approval) {
                $lockedApproval = Pengajuan::where('id', $approval->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedApproval->status !== 'pending') {
                    throw new \Exception('Pengajuan ini sudah diproses sebelumnya.');
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
                ->with('success', 'Pengajuan berhasil ditolak. Stok barang tidak berubah.');
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}