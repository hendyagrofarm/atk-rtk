<?php

namespace App\Support;

use App\Models\Request as Pengajuan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Perhitungan KPI pengajuan barang dari stok.
 *
 * Dipakai bersama oleh halaman laporan dan export Excel agar angkanya identik.
 *
 * Catatan data:
 * - Hanya pengajuan bertipe "stock" yang dihitung (pengajuan custom tidak memakai stok).
 * - Tingkat pemenuhan memakai pengajuan yang sudah diproses (disetujui atau ditolak).
 *   Pengajuan ditolak dihitung sebagai 0 unit terpenuhi.
 * - Angka kekurangan stok berasal dari catatan stok saat pengajuan dibuat
 *   (request_details.stock_at_request). Pengajuan lama tidak punya catatan itu.
 */
class RequestKpi
{
    public static function compute(User $user, array $filters = []): array
    {
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $entityId = self::allowedId($filters['entity_id'] ?? null, $isSuperAdmin, $allowedEntityIds);
        $warehouseId = self::allowedId($filters['warehouse_id'] ?? null, $isSuperAdmin, $allowedWarehouseIds);
        $categoryId = filled($filters['category_id'] ?? null) ? (int) $filters['category_id'] : null;

        $requests = Pengajuan::query()
            ->where('request_type', 'stock')
            ->with(['details.item.category', 'entity', 'warehouse'])
            ->when(
                ! $isSuperAdmin,
                fn ($q) => $q
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->when(filled($filters['start_date'] ?? null), fn ($q) => $q->whereDate('request_date', '>=', $filters['start_date']))
            ->when(filled($filters['end_date'] ?? null), fn ($q) => $q->whereDate('request_date', '<=', $filters['end_date']))
            ->when($entityId, fn ($q) => $q->where('entity_id', $entityId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when(
                $categoryId,
                fn ($q) => $q->whereHas('details.item', fn ($iq) => $iq->where('category_id', $categoryId))
            )
            ->get();

        // Jika difilter kategori, hanya detail pada kategori itu yang dihitung.
        $detailsOf = fn (Pengajuan $request): Collection => $categoryId
            ? $request->details->filter(fn ($d) => (int) $d->item?->category_id === $categoryId)
            : $request->details;

        $processed = $requests->whereIn('status', ['approved', 'rejected']);
        $approved = $requests->where('status', 'approved');
        $rejected = $requests->where('status', 'rejected');

        $unitsRequestedProcessed = $processed->sum(fn ($r) => $detailsOf($r)->sum('quantity'));
        $unitsApproved = $approved->sum(fn ($r) => $detailsOf($r)->sum('approved_quantity'));

        $fullyApproved = $approved->filter(
            fn ($r) => $detailsOf($r)->every(fn ($d) => (int) $d->approved_quantity >= (int) $d->quantity)
        )->count();

        // Seluruh detail beserta induknya, dipakai untuk metrik kekurangan stok.
        $allDetails = $requests->flatMap(
            fn ($r) => $detailsOf($r)->map(fn ($d) => (object) ['request' => $r, 'detail' => $d])
        );

        $withSnapshot = $allDetails->filter(fn ($row) => $row->detail->stock_at_request !== null);
        $shortageRows = $withSnapshot->filter(fn ($row) => (int) $row->detail->shortage_quantity > 0);
        $emptyRows = $withSnapshot->filter(fn ($row) => (int) $row->detail->stock_at_request === 0);

        $processingMinutes = $processed
            ->filter(fn ($r) => $r->approved_at && $r->created_at)
            ->map(fn ($r) => $r->created_at->diffInMinutes($r->approved_at));

        $summary = [
            'total_requests' => $requests->count(),
            'pending' => $requests->where('status', 'pending')->count(),
            'approved' => $approved->count(),
            'rejected' => $rejected->count(),
            'processed' => $processed->count(),
            'units_requested_processed' => (int) $unitsRequestedProcessed,
            'units_approved' => (int) $unitsApproved,
            'fulfillment_rate' => self::percent($unitsApproved, $unitsRequestedProcessed),
            'full_approval_rate' => self::percent($fullyApproved, $processed->count()),
            'detail_total' => $allDetails->count(),
            'detail_with_snapshot' => $withSnapshot->count(),
            'snapshot_since' => $requests
                ->filter(fn ($r) => $detailsOf($r)->contains(fn ($d) => $d->stock_at_request !== null))
                ->min('created_at')?->format('d/m/Y'),
            'shortage_lines' => $shortageRows->count(),
            'shortage_units' => (int) $shortageRows->sum(fn ($row) => (int) $row->detail->shortage_quantity),
            'empty_lines' => $emptyRows->count(),
            'avg_processing_hours' => $processingMinutes->isEmpty()
                ? null
                : round($processingMinutes->avg() / 60, 1),
        ];

        $rejectionReasons = $rejected
            ->groupBy(fn ($r) => $r->rejection_reason_label ?? 'Tanpa alasan (data lama)')
            ->map(fn ($group, $label) => ['label' => $label, 'total' => $group->count()])
            ->sortByDesc('total')
            ->values()
            ->all();

        $perItem = $allDetails
            ->filter(fn ($row) => $row->detail->item_id)
            ->groupBy(fn ($row) => $row->detail->item_id)
            ->map(function (Collection $rows) {
                $item = $rows->first()->detail->item;
                $snap = $rows->filter(fn ($row) => $row->detail->stock_at_request !== null);
                $short = $snap->filter(fn ($row) => (int) $row->detail->shortage_quantity > 0);

                return [
                    'code' => $item?->code ?? '-',
                    'name' => $item?->name ?? '-',
                    'category' => $item?->category?->name ?? '-',
                    'unit' => $item?->unit ?? '-',
                    'request_lines' => $rows->count(),
                    'units_requested' => (int) $rows->sum(fn ($row) => (int) $row->detail->quantity),
                    'units_approved' => (int) $rows
                        ->filter(fn ($row) => $row->request->status === 'approved')
                        ->sum(fn ($row) => (int) $row->detail->approved_quantity),
                    'shortage_lines' => $short->count(),
                    'shortage_units' => (int) $short->sum(fn ($row) => (int) $row->detail->shortage_quantity),
                    'empty_lines' => $snap->filter(fn ($row) => (int) $row->detail->stock_at_request === 0)->count(),
                ];
            })
            ->sort(function (array $a, array $b) {
                return [$b['shortage_units'], $b['shortage_lines'], $b['units_requested']]
                    <=> [$a['shortage_units'], $a['shortage_lines'], $a['units_requested']];
            })
            ->values()
            ->all();

        $perLocation = $requests
            ->groupBy(fn ($r) => $r->entity_id . '-' . $r->warehouse_id)
            ->map(function (Collection $group) use ($detailsOf) {
                $first = $group->first();
                $proc = $group->whereIn('status', ['approved', 'rejected']);
                $requestedProc = $proc->sum(fn ($r) => $detailsOf($r)->sum('quantity'));
                $approvedUnits = $group->where('status', 'approved')
                    ->sum(fn ($r) => $detailsOf($r)->sum('approved_quantity'));
                $shortUnits = $group->flatMap(fn ($r) => $detailsOf($r))
                    ->filter(fn ($d) => $d->stock_at_request !== null)
                    ->sum(fn ($d) => (int) $d->shortage_quantity);

                return [
                    'entity' => $first->entity?->code ?? '-',
                    'warehouse' => $first->warehouse?->name ?? '-',
                    'requests' => $group->count(),
                    'processed' => $proc->count(),
                    'rejected' => $group->where('status', 'rejected')->count(),
                    'units_requested' => (int) $requestedProc,
                    'units_approved' => (int) $approvedUnits,
                    'fulfillment_rate' => self::percent($approvedUnits, $requestedProc),
                    'shortage_units' => (int) $shortUnits,
                ];
            })
            ->sortByDesc('requests')
            ->values()
            ->all();

        return [
            'summary' => $summary,
            'rejection_reasons' => $rejectionReasons,
            'per_item' => $perItem,
            'per_location' => $perLocation,
            'filters' => [
                'entity_id' => $entityId,
                'warehouse_id' => $warehouseId,
                'category_id' => $categoryId,
            ],
        ];
    }

    private static function percent(int|float $part, int|float $whole): ?float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : null;
    }

    private static function allowedId(mixed $value, bool $isSuperAdmin, Collection $allowedIds): ?int
    {
        if (! filled($value)) {
            return null;
        }

        $id = (int) $value;

        return $isSuperAdmin || $allowedIds->contains($id) ? $id : null;
    }
}
