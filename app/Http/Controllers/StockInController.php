<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StockIn;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class StockInController extends Controller
{
    /**
     * Menampilkan riwayat stok masuk sesuai hak akses user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $entities = Entity::query()
            ->where('is_active', true)
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query->whereIn('id', $allowedEntityIds)
            )
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query->whereIn('id', $allowedWarehouseIds)
            )
            ->orderBy('name')
            ->get();

        $items = Item::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $stockInsQuery = StockIn::query()
            ->with([
                'entity',
                'warehouse',
                'item.category',
                'item.itemStocks.entity',
                'item.itemStocks.warehouse',
                'user',
            ]);

        if (! $isSuperAdmin) {
            $stockInsQuery
                ->whereIn('entity_id', $allowedEntityIds)
                ->whereIn('warehouse_id', $allowedWarehouseIds);
        }

        $stockInsQuery
            ->when(
                $request->filled('start_date'),
                fn ($query) => $query->whereDate(
                    'date',
                    '>=',
                    $request->start_date
                )
            )
            ->when(
                $request->filled('end_date'),
                fn ($query) => $query->whereDate(
                    'date',
                    '<=',
                    $request->end_date
                )
            )
            ->when(
                $request->filled('entity_id'),
                function ($query) use (
                    $request,
                    $isSuperAdmin,
                    $allowedEntityIds
                ) {
                    $entityId = (int) $request->entity_id;

                    if (
                        $isSuperAdmin
                        || $allowedEntityIds->contains($entityId)
                    ) {
                        $query->where('entity_id', $entityId);
                    }
                }
            )
            ->when(
                $request->filled('warehouse_id'),
                function ($query) use (
                    $request,
                    $isSuperAdmin,
                    $allowedWarehouseIds
                ) {
                    $warehouseId = (int) $request->warehouse_id;

                    if (
                        $isSuperAdmin
                        || $allowedWarehouseIds->contains($warehouseId)
                    ) {
                        $query->where('warehouse_id', $warehouseId);
                    }
                }
            )
            ->when(
                $request->filled('item_id'),
                fn ($query) => $query->where(
                    'item_id',
                    $request->item_id
                )
            );

        $stockIns = $stockInsQuery
            ->latest('date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('stock-ins.index', [
            'stockIns' => $stockIns,
            'items' => $items,
            'entities' => $entities,
            'warehouses' => $warehouses,
            'currentUser' => $user,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * Menampilkan form tambah stok masuk.
     */
    public function create()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $entities = Entity::query()
            ->where('is_active', true)
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query->whereIn('id', $allowedEntityIds)
            )
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query->whereIn('id', $allowedWarehouseIds)
            )
            ->orderBy('name')
            ->get();

        $items = Item::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock-ins.create', [
            'items' => $items,
            'entities' => $entities,
            'warehouses' => $warehouses,
            'currentUser' => $user,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * Menyimpan stok masuk sesuai hak akses lokasi user.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $validated = $request->validate(
            [
                'entity_id' => [
                    'required',
                    'integer',
                    Rule::exists('entities', 'id')
                        ->where('is_active', true),
                ],

                'warehouse_id' => [
                    'required',
                    'integer',
                    Rule::exists('warehouses', 'id')
                        ->where('is_active', true),
                ],

                'date' => [
                    'required',
                    'date',
                ],

                'items' => ['required', 'array', 'min:1', 'max:100'],

                'items.*.item_id' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::exists('items', 'id')
                        ->where('is_active', true),
                ],

                'items.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'supplier' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'note' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ],
            [
                'entity_id.required' => 'Entitas wajib dipilih.',
                'entity_id.integer' => 'Entitas tidak valid.',
                'entity_id.exists' => 'Entitas tidak ditemukan atau sudah nonaktif.',

                'warehouse_id.required' => 'Gudang wajib dipilih.',
                'warehouse_id.integer' => 'Gudang tidak valid.',
                'warehouse_id.exists' => 'Gudang tidak ditemukan atau sudah nonaktif.',

                'date.required' => 'Tanggal masuk wajib diisi.',
                'date.date' => 'Tanggal masuk tidak valid.',

                'items.required' => 'Minimal pilih satu barang.',
                'items.min' => 'Minimal pilih satu barang.',
                'items.max' => 'Maksimal 100 barang sekali simpan.',

                'items.*.item_id.required' => 'Barang wajib dipilih (barang ke-:position).',
                'items.*.item_id.integer' => 'Barang tidak valid (barang ke-:position).',
                'items.*.item_id.distinct' => 'Barang yang sama tidak boleh dipilih lebih dari satu kali.',
                'items.*.item_id.exists' => 'Barang tidak ditemukan atau sudah nonaktif (barang ke-:position).',

                'items.*.quantity.required' => 'Jumlah masuk wajib diisi (barang ke-:position).',
                'items.*.quantity.integer' => 'Jumlah masuk harus berupa angka bulat (barang ke-:position).',
                'items.*.quantity.min' => 'Jumlah masuk minimal 1 (barang ke-:position).',

                'supplier.string' => 'Supplier harus berupa teks.',
                'supplier.max' => 'Nama supplier maksimal 255 karakter.',

                'note.string' => 'Keterangan harus berupa teks.',
                'note.max' => 'Keterangan maksimal 2.000 karakter.',
            ]
        );

        if (
            ! $isSuperAdmin
            && ! $user->canAccessLocation(
                $validated['entity_id'],
                $validated['warehouse_id']
            )
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Anda tidak memiliki akses ke kombinasi entitas dan gudang tersebut.'
                );
        }

        try {
            DB::transaction(function () use ($validated, $user) {
                foreach ($validated['items'] as $row) {
                    $quantity = (int) $row['quantity'];

                    $item = Item::query()
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->findOrFail($row['item_id']);

                    $itemStock = ItemStock::query()
                        ->where('entity_id', $validated['entity_id'])
                        ->where('warehouse_id', $validated['warehouse_id'])
                        ->where('item_id', $item->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $itemStock) {
                        $itemStock = ItemStock::create([
                            'entity_id' => $validated['entity_id'],
                            'warehouse_id' => $validated['warehouse_id'],
                            'item_id' => $item->id,
                            'current_stock' => 0,
                            'minimum_stock' => $item->minimum_stock ?? 0,
                        ]);
                    }

                    StockIn::create([
                        'entity_id' => $validated['entity_id'],
                        'warehouse_id' => $validated['warehouse_id'],
                        'item_id' => $item->id,
                        'user_id' => $user->id,
                        'quantity' => $quantity,
                        'date' => $validated['date'],
                        'supplier' => $validated['supplier'] ?? null,
                        'note' => $validated['note'] ?? null,
                    ]);

                    $itemStock->current_stock += $quantity;
                    $itemStock->save();

                    $item->current_stock = $item
                        ->itemStocks()
                        ->sum('current_stock');

                    $item->save();
                }
            });

            $count = count($validated['items']);

            return redirect()
                ->route('stock-ins.index')
                ->with(
                    'success',
                    $count > 1
                        ? "Stok masuk {$count} barang berhasil disimpan sesuai entitas dan gudang tujuan."
                        : 'Stok masuk berhasil disimpan sesuai entitas dan gudang tujuan.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menyimpan stok masuk. Silakan periksa data dan coba kembali.'
                );
        }
    }
}
