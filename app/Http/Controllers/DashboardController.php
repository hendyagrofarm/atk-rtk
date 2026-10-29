<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Request as Pengajuan;
use App\Models\RequestApproval;
use App\Models\StockIn;
use App\Support\ApprovalChain;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        // Staff dan approver (SPV/Manager) memakai dashboard personal, bukan dashboard stok gudang.
        if (in_array($user->role, ['staff', 'approver'], true)) {
            return $this->personal($user);
        }

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

    /**
     * Dashboard untuk staff dan approver: fokus pada pengajuan milik sendiri dan
     * pengajuan yang menunggu persetujuannya, bukan pada stok gudang.
     */
    private function personal($user)
    {
        $isApprover = $user->role === 'approver' || ApprovalChain::isParticipantAnywhere($user);

        // Pengajuan milik sendiri.
        $own = Pengajuan::query()->where('user_id', $user->id);

        $ownCounts = [
            'total' => (clone $own)->count(),
            'pending' => (clone $own)->where('status', 'pending')->count(),
            'approved' => (clone $own)->where('status', 'approved')->count(),
            'rejected' => (clone $own)->where('status', 'rejected')->count(),
        ];

        $myRequests = (clone $own)
            ->with(['approvals', 'details.item'])
            ->latest()
            ->limit(5)
            ->get();

        // Pengajuan yang menunggu tindakan user ini sebagai penyetuju.
        $awaiting = collect();
        $processed = ['approved' => 0, 'rejected' => 0];
        $recentProcessed = collect();

        if ($isApprover) {
            $awaiting = Pengajuan::query()
                ->with(['user.position', 'approvals', 'entity'])
                ->where('status', 'pending')
                ->where('user_id', '!=', $user->id)
                ->visibleToApprover($user)
                ->oldest()
                ->get()
                ->filter(fn (Pengajuan $request) => $request->usesApprovalChain()
                    ? ApprovalChain::canAct($user, $request)
                    : $user->role === 'approver')
                ->values();

            $monthStart = now()->startOfMonth();

            $chainDone = RequestApproval::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [RequestApproval::APPROVED, RequestApproval::REJECTED])
                ->where('acted_at', '>=', $monthStart)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $legacyDone = Pengajuan::query()
                ->where('approver_id', $user->id)
                ->whereDoesntHave('approvals')
                ->whereIn('status', ['approved', 'rejected'])
                ->where('approved_at', '>=', $monthStart)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $processed = [
                'approved' => (int) ($chainDone[RequestApproval::APPROVED] ?? 0) + (int) ($legacyDone['approved'] ?? 0),
                'rejected' => (int) ($chainDone[RequestApproval::REJECTED] ?? 0) + (int) ($legacyDone['rejected'] ?? 0),
            ];

            $recentProcessed = Pengajuan::query()
                ->with(['user', 'approvals'])
                ->whereIn('status', ['approved', 'rejected'])
                ->visibleToApprover($user)
                ->latest('updated_at')
                ->limit(5)
                ->get();
        }

        return view('dashboard-personal', [
            'user' => $user,
            'isApprover' => $isApprover,
            'ownCounts' => $ownCounts,
            'myRequests' => $myRequests,
            'awaiting' => $awaiting,
            'processed' => $processed,
            'recentProcessed' => $recentProcessed,
        ]);
    }
}
