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
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Dashboard Statistik Laporan.
     */
    public function dashboard()
    {
        $totalItems = Item::count();

        $totalStock = ItemStock::sum('current_stock');

        $lowStockCount = ItemStock::whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();

        $stockInThisMonth = StockIn::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('quantity');

        $totalRequests = RequestModel::count();

        $pendingRequests = RequestModel::where('status', 'pending')
            ->count();

        $approvedRequests = RequestModel::whereIn('status', ['approved', 'disetujui'])
            ->count();

        $rejectedRequests = RequestModel::whereIn('status', ['rejected', 'ditolak'])
            ->count();

        $stockOutThisMonth = RequestModel::with('details')
            ->whereIn('status', ['approved', 'disetujui'])
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->get()
            ->sum(function ($requestItem) {
                return $requestItem->details->sum('approved_quantity');
            });

        $lowestStockItems = ItemStock::with(['entity', 'item.category'])
            ->orderBy('current_stock', 'asc')
            ->limit(5)
            ->get();

        $latestRequests = RequestModel::with(['user', 'entity', 'approver'])
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

    /**
     * Laporan Stok Barang.
     */
    public function stock(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        $entities = Entity::where('is_active', true)->orderBy('code')->get();

        $query = ItemStock::with(['entity', 'item.category'])
            ->whereHas('item')
            ->whereHas('entity')
            ->join('entities', 'item_stocks.entity_id', '=', 'entities.id')
            ->join('items', 'item_stocks.item_id', '=', 'items.id')
            ->select('item_stocks.*')
            ->orderBy('entities.code')
            ->orderBy('items.name');

        if ($request->filled('entity_id')) {
            $query->where('item_stocks.entity_id', $request->entity_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('item', function ($itemQuery) use ($request) {
                $itemQuery->where('category_id', $request->category_id);
            });
        }

        $itemStocks = $query->get();

        $totalItems = $itemStocks->count();
        $totalStock = $itemStocks->sum('current_stock');

        $lowStockCount = $itemStocks->filter(function ($stock) {
            return $stock->current_stock <= $stock->minimum_stock;
        })->count();

        return view('reports.stock', compact(
            'itemStocks',
            'categories',
            'entities',
            'totalItems',
            'totalStock',
            'lowStockCount'
        ));
    }

    /**
     * Export Excel Laporan Stok Barang.
     */
    public function exportStockExcel(Request $request)
    {
        return Excel::download(
            new StockReportExport($request),
            'laporan-stok-barang.xlsx'
        );
    }

    /**
     * Laporan Stok Masuk.
     */
    public function stockIn(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        $entities = Entity::where('is_active', true)->orderBy('code')->get();

        $query = StockIn::with(['entity', 'item.category', 'user'])
            ->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('item', function ($itemQuery) use ($request) {
                $itemQuery->where('category_id', $request->category_id);
            });
        }

        $stockIns = $query->get();

        $totalTransactions = $stockIns->count();
        $totalQuantity = $stockIns->sum('quantity');

        return view('reports.stock-in', compact(
            'stockIns',
            'categories',
            'entities',
            'totalTransactions',
            'totalQuantity'
        ));
    }

    /**
     * Export Excel Laporan Stok Masuk.
     */
    public function exportStockInExcel(Request $request)
    {
        return Excel::download(
            new StockInReportExport($request),
            'laporan-stok-masuk.xlsx'
        );
    }

    /**
     * Laporan Pengajuan Barang.
     */
    public function requests(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        $entities = Entity::where('is_active', true)->orderBy('code')->get();
        $users = User::orderBy('name')->get();

        $query = RequestModel::with(['user', 'entity', 'details.item.category', 'approver'])
            ->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('request_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('request_date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('details.item', function ($itemQuery) use ($request) {
                $itemQuery->where('category_id', $request->category_id);
            });
        }

        $requests = $query->get();

        $totalRequests = $requests->count();

        $totalItemsRequested = $requests->sum(function ($requestItem) {
            return $requestItem->details->sum('quantity');
        });

        $totalItemsApproved = $requests->sum(function ($requestItem) {
            return $requestItem->details->sum('approved_quantity');
        });

        return view('reports.requests', compact(
            'requests',
            'categories',
            'entities',
            'users',
            'totalRequests',
            'totalItemsRequested',
            'totalItemsApproved'
        ));
    }

    /**
     * Export Excel Laporan Pengajuan Barang.
     */
    public function exportRequestsExcel(Request $request)
    {
        return Excel::download(
            new RequestReportExport($request),
            'laporan-pengajuan-barang.xlsx'
        );
    }

    /**
     * Laporan Barang Keluar.
     */
    public function stockOut(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        $entities = Entity::where('is_active', true)->orderBy('code')->get();
        $users = User::orderBy('name')->get();

        $query = RequestModel::with(['user', 'entity', 'details.item.category', 'approver'])
            ->whereIn('status', ['approved', 'disetujui'])
            ->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('approved_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('approved_at', '<=', $request->end_date);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('details.item', function ($itemQuery) use ($request) {
                $itemQuery->where('category_id', $request->category_id);
            });
        }

        $requests = $query->get();

        $totalApprovedRequests = $requests->count();

        $totalStockOut = $requests->sum(function ($requestItem) {
            return $requestItem->details->sum('approved_quantity');
        });

        return view('reports.stock-out', compact(
            'requests',
            'categories',
            'entities',
            'users',
            'totalApprovedRequests',
            'totalStockOut'
        ));
    }

    /**
     * Export Excel Laporan Barang Keluar.
     */
    public function exportStockOutExcel(Request $request)
    {
        return Excel::download(
            new StockOutReportExport($request),
            'laporan-barang-keluar.xlsx'
        );
    }
}