<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Staff\RequestController as StaffRequestController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\UserController;
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
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Route khusus Admin
|--------------------------------------------------------------------------
*/
Route::get('/admin/dashboard', function () {
    return redirect()->route('dashboard');
})->middleware(['auth', 'verified', 'role:admin'])->name('admin.dashboard');

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
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
        Route::get('/requests', [StaffRequestController::class, 'index'])
            ->name('requests.index');

        Route::get('/requests/create', [StaffRequestController::class, 'create'])
            ->name('requests.create');

        Route::post('/requests', [StaffRequestController::class, 'store'])
            ->name('requests.store');

        Route::get('/requests/{id}', [StaffRequestController::class, 'show'])
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
Route::middleware(['auth', 'verified', 'role:admin,approver'])
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