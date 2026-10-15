<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ApprovalStepController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\FpbController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\KpiReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Staff\RequestController as StaffRequestController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockAuditController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockTransferReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman Awal
|--------------------------------------------------------------------------
| Jika belum login  -> langsung ke halaman login
| Jika sudah login  -> langsung ke dashboard
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Dashboard umum
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');


/*
|--------------------------------------------------------------------------
| Master Entitas - Khusus Super Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {
    Route::resource('entities', EntityController::class)
        ->except(['show']);

    Route::patch(
        '/entities/{entity}/toggle-status',
        [EntityController::class, 'toggleStatus']
    )->name('entities.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Jabatan dan Alur Approval Pengajuan Custom
    |--------------------------------------------------------------------------
    */
    Route::resource('positions', PositionController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('approval-steps', ApprovalStepController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::patch(
        '/approval-steps/{approvalStep}/move',
        [ApprovalStepController::class, 'move']
    )->name('approval-steps.move');
});

/*
|--------------------------------------------------------------------------
| Route khusus Admin
|--------------------------------------------------------------------------
*/
Route::get('/admin/dashboard', function () {
    return redirect()->route('dashboard');
})->middleware(['auth', 'verified', 'role:super_admin,admin'])->name('admin.dashboard');

Route::middleware(['auth', 'verified', 'role:super_admin,admin'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Manajemen User Admin
    |--------------------------------------------------------------------------
    */
    Route::resource('users', UserController::class)
        ->except(['show']);

    Route::patch('/users/{user}/activate', [UserController::class, 'activate'])
        ->name('users.activate');

    /*
    |--------------------------------------------------------------------------
    | Master Data
    |--------------------------------------------------------------------------
    */
    Route::resource('warehouses', WarehouseController::class)
        ->except(['show']);

    Route::patch(
        '/warehouses/{warehouse}/toggle-status',
        [WarehouseController::class, 'toggleStatus']
        )->name('warehouses.toggle-status');

    Route::resource('categories', CategoryController::class);

    Route::resource('items', ItemController::class);

    /*
    |--------------------------------------------------------------------------
    | Stok Masuk
    |--------------------------------------------------------------------------
    */
    Route::resource('stock-ins', StockInController::class)
        ->only(['index', 'create', 'store']);

    /*
    |--------------------------------------------------------------------------
    | Mutasi Stok Antar Gudang
    |--------------------------------------------------------------------------
    */
    Route::resource('stock-transfers', StockTransferController::class)
        ->only(['index', 'create', 'store']);


    /*
    |--------------------------------------------------------------------------
    | Audit dan Penyesuaian Stok
    |--------------------------------------------------------------------------
    */
    Route::resource('stock-audits', StockAuditController::class)
        ->only(['index', 'create', 'store']);

    /*
    |--------------------------------------------------------------------------
    | Route Laporan Admin
    |--------------------------------------------------------------------------
    */
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard'])
        ->name('reports.dashboard');

    Route::get('/reports/stock', [ReportController::class, 'stock'])
        ->name('reports.stock');

    Route::get('/reports/stock/export-excel', [ReportController::class, 'exportStockExcel'])
        ->name('reports.stock.export-excel');

    Route::get('/reports/stock-in', [ReportController::class, 'stockIn'])
        ->name('reports.stock-in');

    Route::get('/reports/stock-in/export-excel', [ReportController::class, 'exportStockInExcel'])
        ->name('reports.stock-in.export-excel');

    Route::get('/reports/requests', [ReportController::class, 'requests'])
        ->name('reports.requests');

    Route::get('/reports/requests/export-excel', [ReportController::class, 'exportRequestsExcel'])
        ->name('reports.requests.export-excel');

    Route::get('/reports/stock-out', [ReportController::class, 'stockOut'])
        ->name('reports.stock-out');

    Route::get('/reports/stock-out/export-excel', [ReportController::class, 'exportStockOutExcel'])
        ->name('reports.stock-out.export-excel');

    Route::get('/reports/kpi', [KpiReportController::class, 'index'])
        ->name('reports.kpi');

    Route::get('/reports/kpi/export-excel', [KpiReportController::class, 'exportExcel'])
        ->name('reports.kpi.export-excel');

    Route::get(
        '/reports/stock-transfers',
        [StockTransferReportController::class, 'index']
    )->name('reports.stock-transfers');

    Route::get(
        '/reports/stock-transfers/export-excel',
        [StockTransferReportController::class, 'exportExcel']
    )->name('reports.stock-transfers.export-excel');
});

/*
|--------------------------------------------------------------------------
| Route khusus Staff
|--------------------------------------------------------------------------
*/
Route::get('/staff/dashboard', function () {
    return redirect()->route('dashboard');
})->middleware(['auth', 'verified', 'role:staff'])->name('staff.dashboard');

Route::middleware(['auth', 'verified', 'role:staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Pengajuan dari Stok (khusus staff)
        |--------------------------------------------------------------------------
        */
        Route::get(
            '/requests/create',
            [StaffRequestController::class, 'create']
        )->name('requests.create');

        Route::post(
            '/requests',
            [StaffRequestController::class, 'store']
        )->name('requests.store');
    });

/*
|--------------------------------------------------------------------------
| Daftar Pengajuan dan Pengajuan Baru / Custom (FPB)
|--------------------------------------------------------------------------
| Staff mengajukan untuk dirinya sendiri. Admin dan super admin dapat
| membuat pengajuan custom atas nama PIC dari divisi lain.
*/
Route::middleware(['auth', 'verified', 'role:staff,admin,super_admin'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get(
            '/requests',
            [StaffRequestController::class, 'index']
        )->name('requests.index');

        Route::get(
            '/requests/custom/create',
            [StaffRequestController::class, 'createCustom']
        )->name('requests.custom.create');

        Route::post(
            '/requests/custom',
            [StaffRequestController::class, 'storeCustom']
        )->name('requests.custom.store');

        /*
        |--------------------------------------------------------------------------
        | Detail Pengajuan
        |--------------------------------------------------------------------------
        | Route ini wajib berada setelah route custom.
        |--------------------------------------------------------------------------
        */
        Route::get(
            '/requests/{id}',
            [StaffRequestController::class, 'show']
        )
            ->whereNumber('id')
            ->name('requests.show');
    });

/*
|--------------------------------------------------------------------------
| Route khusus Approver
|--------------------------------------------------------------------------
*/
Route::get('/approver/dashboard', function () {
    return redirect()->route('dashboard');
})->middleware(['auth', 'verified', 'role:approver'])->name('approver.dashboard');

/*
|--------------------------------------------------------------------------
| Route Approval Pengajuan
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:super_admin,admin,approver'])
    ->prefix('approvals')
    ->name('approvals.')
    ->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])
            ->name('index');

        Route::get('/{approval}', [ApprovalController::class, 'show'])
            ->name('show');

        Route::post('/{approval}/approve', [ApprovalController::class, 'approve'])
            ->name('approve');

        Route::post('/{approval}/reject', [ApprovalController::class, 'reject'])
            ->name('reject');
    });

/*
|--------------------------------------------------------------------------
| Cetak Formulir Pengajuan Barang (FPB) - pengajuan custom
|--------------------------------------------------------------------------
| Hak akses diperiksa di FpbController: pemohon, super admin, pihak yang
| berada pada alur approval, atau admin/approver dengan akses lokasi.
*/
Route::get('/fpb/{pengajuan}', [FpbController::class, 'print'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('pengajuan')
    ->name('fpb.print');

/*
|--------------------------------------------------------------------------
| Profile User
|--------------------------------------------------------------------------
| User hanya boleh update profile dan password.
| Fitur hapus akun sendiri dinonaktifkan.
| Pengelolaan user hanya melalui Admin -> Manajemen User.
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    /*
    |--------------------------------------------------------------------------
    | Route hapus akun profile sengaja tidak dibuat
    |--------------------------------------------------------------------------
    | Sebelumnya Breeze memiliki:
    | Route::delete('/profile', [ProfileController::class, 'destroy'])
    |     ->name('profile.destroy');
    |
    | Route tersebut dinonaktifkan agar user tidak bisa menghapus akunnya sendiri.
    */
});

require __DIR__ . '/auth.php';