<?php

namespace App\Http\Controllers;

use App\Exports\KpiReportExport;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Warehouse;
use App\Support\RequestKpi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class KpiReportController extends Controller
{
    private const FILTERS = ['start_date', 'end_date', 'entity_id', 'warehouse_id', 'category_id'];

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ]);

        $kpi = RequestKpi::compute($user, $request->only(self::FILTERS));

        $entities = Entity::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $user->allowedEntityIds()))
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $user->allowedWarehouseIds()))
            ->orderBy('name')
            ->get();

        $categories = Category::orderBy('name')->get();

        return view('reports.kpi', [
            'summary' => $kpi['summary'],
            'rejectionReasons' => $kpi['rejection_reasons'],
            'perItem' => $kpi['per_item'],
            'perLocation' => $kpi['per_location'],
            'entityId' => $kpi['filters']['entity_id'],
            'warehouseId' => $kpi['filters']['warehouse_id'],
            'categoryId' => $kpi['filters']['category_id'],
            'entities' => $entities,
            'warehouses' => $warehouses,
            'categories' => $categories,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $kpi = RequestKpi::compute(Auth::user(), $request->only(self::FILTERS));

        return Excel::download(
            new KpiReportExport($kpi),
            'laporan-kpi-pengajuan.xlsx'
        );
    }
}
