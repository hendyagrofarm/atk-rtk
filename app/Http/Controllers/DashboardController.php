<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Request as Pengajuan;
use App\Models\StockIn;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $itemStockQuery = ItemStock::query()
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            );

        $requestQuery = Pengajuan::query();

        if ($user->role === 'staff') {
            $requestQuery->where('user_id', $user->id);
        } elseif (! $isSuperAdmin) {
            $requestQuery
                ->whereIn('entity_id', $allowedEntityIds)
                ->whereIn('warehouse_id', $allowedWarehouseIds);
        }

        $totalItems = Item::where('is_active', true)->count();
        $totalStock = (clone $itemStockQuery)->sum('current_stock');

        $lowStockCount = (clone $itemStockQuery)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();

        $pendingRequestCount = (clone $requestQuery)
            ->where('status', 'pending')
            ->count();

        $stockInThisMonth = StockIn::query()
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('quantity');

        $lowStockItems = collect();
        $latestRequests = collect();

        if (in_array($user->role, ['super_admin', 'admin', 'approver'])) {
            $lowStockItems = ItemStock::query()
                ->with(['entity', 'warehouse', 'item.category'])
                ->whereHas('item', fn ($query) => $query->where('is_active', true))
                ->when(
                    ! $isSuperAdmin,
                    fn ($query) => $query
                        ->whereIn('entity_id', $allowedEntityIds)
                        ->whereIn('warehouse_id', $allowedWarehouseIds)
                )
                ->orderBy('current_stock')
                ->orderBy('minimum_stock')
                ->limit(5)
                ->get();

            $latestRequests = Pengajuan::query()
                ->with(['user', 'entity', 'warehouse', 'approver'])
                ->when(
                    ! $isSuperAdmin,
                    fn ($query) => $query
                        ->whereIn('entity_id', $allowedEntityIds)
                        ->whereIn('warehouse_id', $allowedWarehouseIds)
                )
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('dashboard', compact(
            'totalItems',
            'totalStock',
            'lowStockCount',
            'pendingRequestCount',
            'stockInThisMonth',
            'lowStockItems',
            'latestRequests'
        ));
    }
}
