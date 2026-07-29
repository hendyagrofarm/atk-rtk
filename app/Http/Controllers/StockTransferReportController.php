<?php

namespace App\Http\Controllers;

use App\Exports\StockTransferReportExport;
use App\Models\Entity;
use App\Models\Item;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class StockTransferReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $entities = Entity::active()
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedEntityIds))
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::active()
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedWarehouseIds))
            ->ordered()
            ->get();

        $items = Item::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $query = StockTransfer::with([
            'entity',
            'sourceWarehouse',
            'destinationWarehouse',
            'item.category',
            'user',
        ]);

        if (! $isSuperAdmin) {
            $query->whereIn('entity_id', $allowedEntityIds)
                ->whereIn('source_warehouse_id', $allowedWarehouseIds)
                ->whereIn('destination_warehouse_id', $allowedWarehouseIds);
        }

        $this->applyFilters(
            $query,
            $request,
            $isSuperAdmin,
            $allowedEntityIds,
            $allowedWarehouseIds
        );

        $transfers = $query
            ->latest('transfer_date')
            ->latest('id')
            ->get();

        $totalTransactions = $transfers->count();
        $totalQuantity = $transfers->sum('quantity');
        $totalRoutes = $transfers
            ->map(fn ($transfer) =>
                $transfer->entity_id . '-' .
                $transfer->source_warehouse_id . '-' .
                $transfer->destination_warehouse_id
            )
            ->unique()
            ->count();

        return view('reports.stock-transfers', compact(
            'transfers',
            'entities',
            'warehouses',
            'items',
            'users',
            'totalTransactions',
            'totalQuantity',
            'totalRoutes'
        ));
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(
            new StockTransferReportExport($request),
            'laporan-mutasi-stok-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    private function applyFilters(
        $query,
        Request $request,
        bool $isSuperAdmin,
        $allowedEntityIds,
        $allowedWarehouseIds
    ): void {
        $query
            ->when($request->filled('start_date'),
                fn ($q) => $q->whereDate('transfer_date', '>=', $request->start_date))
            ->when($request->filled('end_date'),
                fn ($q) => $q->whereDate('transfer_date', '<=', $request->end_date))
            ->when($request->filled('entity_id'), function ($q) use ($request, $isSuperAdmin, $allowedEntityIds) {
                $id = (int) $request->entity_id;
                if ($isSuperAdmin || $allowedEntityIds->contains($id)) {
                    $q->where('entity_id', $id);
                }
            })
            ->when($request->filled('source_warehouse_id'), function ($q) use ($request, $isSuperAdmin, $allowedWarehouseIds) {
                $id = (int) $request->source_warehouse_id;
                if ($isSuperAdmin || $allowedWarehouseIds->contains($id)) {
                    $q->where('source_warehouse_id', $id);
                }
            })
            ->when($request->filled('destination_warehouse_id'), function ($q) use ($request, $isSuperAdmin, $allowedWarehouseIds) {
                $id = (int) $request->destination_warehouse_id;
                if ($isSuperAdmin || $allowedWarehouseIds->contains($id)) {
                    $q->where('destination_warehouse_id', $id);
                }
            })
            ->when($request->filled('item_id'),
                fn ($q) => $q->where('item_id', $request->item_id))
            ->when($request->filled('user_id'),
                fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);

                $q->where(function ($sub) use ($search) {
                    $sub->where('transfer_number', 'like', "%{$search}%")
                        ->orWhereHas('item', function ($itemQuery) use ($search) {
                            $itemQuery->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        })
                        ->orWhere('note', 'like', "%{$search}%");
                });
            });
    }
}
