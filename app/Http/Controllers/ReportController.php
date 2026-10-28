<?php

namespace App\Http\Controllers;

use App\Exports\RequestReportExport;
use App\Exports\StockInReportExport;
use App\Exports\StockOutReportExport;
use App\Exports\StockReportExport;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Request as RequestModel;
use App\Models\StockIn;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function dashboard()
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

        $requestQuery = RequestModel::query()
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            );

        $stockInQuery = StockIn::query()
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            );

        $totalItems = Item::count();
        $totalStock = (clone $itemStockQuery)->sum('current_stock');
        $lowStockCount = (clone $itemStockQuery)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();

        $stockInThisMonth = (clone $stockInQuery)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('quantity');

        $totalRequests = (clone $requestQuery)->count();
        $pendingRequests = (clone $requestQuery)->where('status', 'pending')->count();
        $approvedRequests = (clone $requestQuery)
            ->whereIn('status', ['approved', 'disetujui'])
            ->count();
        $rejectedRequests = (clone $requestQuery)
            ->whereIn('status', ['rejected', 'ditolak'])
            ->count();

        $stockOutThisMonth = (clone $requestQuery)
            ->with('details')
            ->whereIn('status', ['approved', 'disetujui'])
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->get()
            ->sum(fn ($requestItem) => $requestItem->details->sum('approved_quantity'));

        $lowestStockItems = ItemStock::query()
            ->with(['entity', 'warehouse', 'item.category'])
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        $latestRequests = RequestModel::query()
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

        return view('reports.dashboard', compact(
            'totalItems',
            'totalStock',
            'lowStockCount',
            'stockInThisMonth',
            'totalRequests',
            'pendingRequests',
            'approvedRequests',
            'rejectedRequests',
            'stockOutThisMonth',
            'lowestStockItems',
            'latestRequests'
        ));
    }

    public function stock(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $categories = Category::orderBy('name')->get();

        $entities = Entity::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedEntityIds))
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedWarehouseIds))
            ->orderBy('name')
            ->get();

        $entityId = $this->allowedFilterId(
            $request->entity_id,
            $isSuperAdmin,
            $allowedEntityIds
        );

        $warehouseId = $this->allowedFilterId(
            $request->warehouse_id,
            $isSuperAdmin,
            $allowedWarehouseIds
        );

        $categoryId = $request->category_id;
        $stockStatus = $request->stock_status;
        $search = trim((string) $request->search);

        $itemStocks = ItemStock::query()
            ->with(['entity', 'warehouse', 'item.category'])
            ->whereHas('item')
            ->whereHas('entity')
            ->whereHas('warehouse')
            ->when(
                ! $isSuperAdmin,
                fn ($q) => $q
                    ->whereIn('item_stocks.entity_id', $allowedEntityIds)
                    ->whereIn('item_stocks.warehouse_id', $allowedWarehouseIds)
            )
            ->when($entityId, fn ($q) => $q->where('item_stocks.entity_id', $entityId))
            ->when($warehouseId, fn ($q) => $q->where('item_stocks.warehouse_id', $warehouseId))
            ->when(
                filled($categoryId),
                fn ($q) => $q->whereHas(
                    'item',
                    fn ($iq) => $iq->where('category_id', $categoryId)
                )
            )
            ->when(
                $search !== '',
                fn ($q) => $q->whereHas('item', function ($iq) use ($search) {
                    $iq->where(function ($sq) use ($search) {
                        $sq->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
                })
            )
            ->when(
                $stockStatus === 'low',
                fn ($q) => $q->whereColumn(
                    'item_stocks.current_stock',
                    '<=',
                    'item_stocks.minimum_stock'
                )
            )
            ->when(
                $stockStatus === 'safe',
                fn ($q) => $q->whereColumn(
                    'item_stocks.current_stock',
                    '>',
                    'item_stocks.minimum_stock'
                )
            )
            ->join('entities', 'item_stocks.entity_id', '=', 'entities.id')
            ->join('warehouses', 'item_stocks.warehouse_id', '=', 'warehouses.id')
            ->join('items', 'item_stocks.item_id', '=', 'items.id')
            ->select('item_stocks.*')
            ->orderBy('entities.code')
            ->orderBy('warehouses.name')
            ->orderBy('items.name')
            ->get();

        $totalItems = $itemStocks->count();
        $totalStock = $itemStocks->sum('current_stock');
        $lowStockCount = $itemStocks
            ->filter(fn ($stock) => $stock->current_stock <= $stock->minimum_stock)
            ->count();

        $totalLocations = $itemStocks
            ->map(fn ($stock) => $stock->entity_id . '-' . $stock->warehouse_id)
            ->unique()
            ->count();

        return view('reports.stock', compact(
            'itemStocks',
            'categories',
            'entities',
            'warehouses',
            'totalItems',
            'totalStock',
            'lowStockCount',
            'totalLocations',
            'entityId',
            'warehouseId',
            'categoryId',
            'stockStatus',
            'search'
        ));
    }

    public function exportStockExcel(Request $request)
    {
        return Excel::download(
            new StockReportExport($request),
            'laporan-stok-barang.xlsx'
        );
    }

    public function stockIn(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $categories = Category::orderBy('name')->get();

        $entities = Entity::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedEntityIds))
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedWarehouseIds))
            ->orderBy('name')
            ->get();

        $entityId = $this->allowedFilterId(
            $request->entity_id,
            $isSuperAdmin,
            $allowedEntityIds
        );

        $warehouseId = $this->allowedFilterId(
            $request->warehouse_id,
            $isSuperAdmin,
            $allowedWarehouseIds
        );

        $query = StockIn::query()
            ->with(['entity', 'warehouse', 'item.category', 'user'])
            ->when(
                ! $isSuperAdmin,
                fn ($q) => $q
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('date', '<=', $request->end_date))
            ->when($entityId, fn ($q) => $q->where('entity_id', $entityId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->whereHas(
                    'item',
                    fn ($iq) => $iq->where('category_id', $request->category_id)
                )
            )
            ->latest();

        $stockIns = $query->get();
        $totalTransactions = $stockIns->count();
        $totalQuantity = $stockIns->sum('quantity');

        return view('reports.stock-in', compact(
            'stockIns',
            'categories',
            'entities',
            'warehouses',
            'entityId',
            'warehouseId',
            'totalTransactions',
            'totalQuantity'
        ));
    }

    public function exportStockInExcel(Request $request)
    {
        return Excel::download(
            new StockInReportExport($request),
            'laporan-stok-masuk.xlsx'
        );
    }

    public function requests(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $categories = Category::orderBy('name')->get();

        $entities = Entity::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedEntityIds))
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedWarehouseIds))
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        $entityId = $this->allowedFilterId(
            $request->entity_id,
            $isSuperAdmin,
            $allowedEntityIds
        );

        $warehouseId = $this->allowedFilterId(
            $request->warehouse_id,
            $isSuperAdmin,
            $allowedWarehouseIds
        );

        $query = RequestModel::query()
            ->with([
                'user',
                'entity',
                'warehouse',
                'details.item.category',
                'approver',
            ])
            ->when(
                ! $isSuperAdmin,
                fn ($q) => $q
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('request_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('request_date', '<=', $request->end_date))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($entityId, fn ($q) => $q->where('entity_id', $entityId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->whereHas(
                    'details.item',
                    fn ($iq) => $iq->where('category_id', $request->category_id)
                )
            )
            ->latest();

        $requests = $query->get();

        $totalRequests = $requests->count();
        $totalItemsRequested = $requests->sum(
            fn ($requestItem) => $requestItem->details->sum('quantity')
        );
        $totalItemsApproved = $requests->sum(
            fn ($requestItem) => $requestItem->details->sum('approved_quantity')
        );

        return view('reports.requests', compact(
            'requests',
            'categories',
            'entities',
            'warehouses',
            'users',
            'entityId',
            'warehouseId',
            'totalRequests',
            'totalItemsRequested',
            'totalItemsApproved'
        ));
    }

    public function exportRequestsExcel(Request $request)
    {
        return Excel::download(
            new RequestReportExport($request),
            'laporan-pengajuan-barang.xlsx'
        );
    }

    public function stockOut(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $categories = Category::orderBy('name')->get();

        $entities = Entity::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedEntityIds))
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedWarehouseIds))
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        $entityId = $this->allowedFilterId(
            $request->entity_id,
            $isSuperAdmin,
            $allowedEntityIds
        );

        $warehouseId = $this->allowedFilterId(
            $request->warehouse_id,
            $isSuperAdmin,
            $allowedWarehouseIds
        );

        $query = RequestModel::query()
            ->with([
                'user',
                'entity',
                'warehouse',
                'details.item.category',
                'approver',
            ])
            ->whereIn('status', ['approved', 'disetujui'])
            ->when(
                ! $isSuperAdmin,
                fn ($q) => $q
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('approved_at', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('approved_at', '<=', $request->end_date))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($entityId, fn ($q) => $q->where('entity_id', $entityId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->whereHas(
                    'details.item',
                    fn ($iq) => $iq->where('category_id', $request->category_id)
                )
            )
            ->latest();

        $requests = $query->get();

        $totalApprovedRequests = $requests->count();
        $totalStockOut = $requests->sum(
            fn ($requestItem) => $requestItem->details->sum('approved_quantity')
        );

        // Pengeluaran langsung oleh admin (tanpa pengajuan) ikut dilaporkan.
        $directOuts = \App\Models\StockOut::query()
            ->visibleTo($user)
            ->filtered([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'entity_id' => $entityId,
                'warehouse_id' => $warehouseId,
                'category_id' => $request->category_id,
                'issuer_id' => $request->user_id,
            ], $user)
            ->with(['entity', 'warehouse', 'issuer', 'items.item.category'])
            ->latest('issue_date')
            ->latest('id')
            ->get();

        $totalDirectOuts = $directOuts->count();
        $totalDirectUnits = $directOuts->sum(fn ($out) => $out->items
            ->filter(fn ($line) => ! $request->filled('category_id')
                || (int) $line->item?->category_id === (int) $request->category_id)
            ->sum('quantity'));

        return view('reports.stock-out', compact(
            'directOuts',
            'totalDirectOuts',
            'totalDirectUnits',
            'requests',
            'categories',
            'entities',
            'warehouses',
            'users',
            'entityId',
            'warehouseId',
            'totalApprovedRequests',
            'totalStockOut'
        ));
    }

    public function exportStockOutExcel(Request $request)
    {
        return Excel::download(
            new StockOutReportExport($request),
            'laporan-barang-keluar.xlsx'
        );
    }

    private function allowedFilterId(
        mixed $value,
        bool $isSuperAdmin,
        $allowedIds
    ): ?int {
        if (! filled($value)) {
            return null;
        }

        $id = (int) $value;

        return $isSuperAdmin || $allowedIds->contains($id)
            ? $id
            : null;
    }
}
