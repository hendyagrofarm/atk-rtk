<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class ItemController extends Controller
{
    /**
     * Menampilkan master barang beserta stok per lokasi
     * sesuai hak akses user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $search = trim((string) $request->input('search'));
        $categoryId = $request->input('category_id');
        $entityId = $request->input('entity_id');
        $warehouseId = $request->input('warehouse_id');
        $status = $request->input('status');
        $stockStatus = $request->input('stock_status');

        /*
        |--------------------------------------------------------------------------
        | Pilihan filter sesuai hak akses
        |--------------------------------------------------------------------------
        */
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

        $categories = Category::query()
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Validasi ringan filter lokasi
        |--------------------------------------------------------------------------
        | Nilai filter yang tidak termasuk akses user tidak akan digunakan.
        */
        $selectedEntityId = filled($entityId)
            && (
                $isSuperAdmin
                || $allowedEntityIds->contains((int) $entityId)
            )
                ? (int) $entityId
                : null;

        $selectedWarehouseId = filled($warehouseId)
            && (
                $isSuperAdmin
                || $allowedWarehouseIds->contains((int) $warehouseId)
            )
                ? (int) $warehouseId
                : null;

        /*
        |--------------------------------------------------------------------------
        | Query barang dan stok lokasi
        |--------------------------------------------------------------------------
        */
        $items = Item::query()
            ->with([
                'category',
                'itemStocks' => function ($query) use (
                    $isSuperAdmin,
                    $allowedEntityIds,
                    $allowedWarehouseIds,
                    $selectedEntityId,
                    $selectedWarehouseId,
                    $stockStatus
                ) {
                    $query
                        ->with([
                            'entity',
                            'warehouse',
                        ])
                        ->when(
                            ! $isSuperAdmin,
                            fn ($stockQuery) => $stockQuery
                                ->whereIn('entity_id', $allowedEntityIds)
                                ->whereIn('warehouse_id', $allowedWarehouseIds)
                        )
                        ->when(
                            $selectedEntityId,
                            fn ($stockQuery) => $stockQuery
                                ->where('entity_id', $selectedEntityId)
                        )
                        ->when(
                            $selectedWarehouseId,
                            fn ($stockQuery) => $stockQuery
                                ->where('warehouse_id', $selectedWarehouseId)
                        )
                        ->when(
                            $stockStatus === 'low',
                            fn ($stockQuery) => $stockQuery
                                ->whereColumn(
                                    'current_stock',
                                    '<=',
                                    'minimum_stock'
                                )
                        )
                        ->when(
                            $stockStatus === 'safe',
                            fn ($stockQuery) => $stockQuery
                                ->whereColumn(
                                    'current_stock',
                                    '>',
                                    'minimum_stock'
                                )
                        )
                        ->orderBy('entity_id')
                        ->orderBy('warehouse_id');
                },
            ])
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where('code', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                    });
                }
            )
            ->when(
                filled($categoryId),
                fn ($query) => $query->where('category_id', $categoryId)
            )
            ->when(
                $status !== null && $status !== '',
                fn ($query) => $query->where(
                    'is_active',
                    (bool) $status
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Saat filter lokasi atau status stok dipakai, tampilkan hanya barang
        | yang mempunyai stok sesuai filter tersebut.
        |--------------------------------------------------------------------------
        */
        if (
            $selectedEntityId
            || $selectedWarehouseId
            || in_array($stockStatus, ['low', 'safe'], true)
        ) {
            $items->whereHas(
                'itemStocks',
                function ($query) use (
                    $isSuperAdmin,
                    $allowedEntityIds,
                    $allowedWarehouseIds,
                    $selectedEntityId,
                    $selectedWarehouseId,
                    $stockStatus
                ) {
                    $query
                        ->when(
                            ! $isSuperAdmin,
                            fn ($stockQuery) => $stockQuery
                                ->whereIn('entity_id', $allowedEntityIds)
                                ->whereIn('warehouse_id', $allowedWarehouseIds)
                        )
                        ->when(
                            $selectedEntityId,
                            fn ($stockQuery) => $stockQuery
                                ->where('entity_id', $selectedEntityId)
                        )
                        ->when(
                            $selectedWarehouseId,
                            fn ($stockQuery) => $stockQuery
                                ->where('warehouse_id', $selectedWarehouseId)
                        )
                        ->when(
                            $stockStatus === 'low',
                            fn ($stockQuery) => $stockQuery
                                ->whereColumn(
                                    'current_stock',
                                    '<=',
                                    'minimum_stock'
                                )
                        )
                        ->when(
                            $stockStatus === 'safe',
                            fn ($stockQuery) => $stockQuery
                                ->whereColumn(
                                    'current_stock',
                                    '>',
                                    'minimum_stock'
                                )
                        );
                }
            );
        }

        $items = $items
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'categories' => $categories,
            'entities' => $entities,
            'warehouses' => $warehouses,
            'search' => $search,
            'categoryId' => $categoryId,
            'entityId' => $selectedEntityId,
            'warehouseId' => $selectedWarehouseId,
            'status' => $status,
            'stockStatus' => $stockStatus,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create()
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return view('items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'items' => ['required', 'array', 'min:1', 'max:50'],
                'items.*.code' => [
                    'required',
                    'string',
                    'max:100',
                    'distinct:ignore_case',
                    Rule::unique('items', 'code'),
                ],
                'items.*.name' => ['required', 'string', 'max:255'],
                'items.*.category_id' => ['required', 'exists:categories,id'],
                'items.*.type' => ['required', Rule::in(['ATK', 'RTK'])],
                'items.*.unit' => ['required', 'string', 'max:50'],
                'items.*.minimum_stock' => ['required', 'integer', 'min:0'],
                'items.*.storage_location' => ['nullable', 'string', 'max:255'],
            ],
            [
                'items.required' => 'Minimal isi satu barang.',
                'items.min' => 'Minimal isi satu barang.',
                'items.max' => 'Maksimal 50 barang sekali simpan.',
                'items.*.code.required' => 'Kode barang wajib diisi (barang ke-:position).',
                'items.*.code.unique' => 'Kode barang ":input" sudah digunakan.',
                'items.*.code.distinct' => 'Kode barang ":input" diisi lebih dari satu kali.',
                'items.*.name.required' => 'Nama barang wajib diisi (barang ke-:position).',
                'items.*.category_id.required' => 'Kategori wajib dipilih (barang ke-:position).',
                'items.*.category_id.exists' => 'Kategori tidak valid (barang ke-:position).',
                'items.*.type.required' => 'Jenis barang wajib dipilih (barang ke-:position).',
                'items.*.type.in' => 'Jenis barang harus ATK atau RTK (barang ke-:position).',
                'items.*.unit.required' => 'Satuan wajib diisi (barang ke-:position).',
                'items.*.minimum_stock.required' => 'Stok minimum wajib diisi (barang ke-:position).',
                'items.*.minimum_stock.integer' => 'Stok minimum harus berupa angka (barang ke-:position).',
                'items.*.minimum_stock.min' => 'Stok minimum tidak boleh kurang dari 0 (barang ke-:position).',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Stok awal lokasi tidak dibuat dari Master Barang
        |--------------------------------------------------------------------------
        | Stok per entitas dan gudang dibuat saat transaksi Stok Masuk pertama.
        |--------------------------------------------------------------------------
        */
        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $row) {
                Item::create([
                    'category_id' => $row['category_id'],
                    'code' => trim($row['code']),
                    'name' => trim($row['name']),
                    'type' => $row['type'],
                    'unit' => trim($row['unit']),
                    'minimum_stock' => $row['minimum_stock'],
                    'storage_location' => $row['storage_location'] ?? null,
                    'current_stock' => 0,
                    'is_active' => true,
                ]);
            }
        });

        $count = count($validated['items']);

        return redirect()
            ->route('items.index')
            ->with(
                'success',
                ($count > 1 ? "{$count} barang berhasil ditambahkan." : 'Barang berhasil ditambahkan.')
                    . ' Stok lokasi akan terbentuk saat transaksi stok masuk.'
            );
    }

    public function show(Item $item)
    {
        return redirect()->route('items.index');
    }

    public function edit(Item $item)
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return view('items.edit', compact('item', 'categories'));
    }

    public function update(Request $request, Item $item)
    {
        $validated = $this->validateItem($request, $item);
        $validated['is_active'] = $request->boolean('is_active');

        /*
        |--------------------------------------------------------------------------
        | current_stock utama tidak boleh diubah manual
        |--------------------------------------------------------------------------
        | Nilainya tetap merupakan total seluruh item_stocks.
        |--------------------------------------------------------------------------
        */
        unset($validated['current_stock']);

        $item->update($validated);

        /*
        |--------------------------------------------------------------------------
        | Sinkron stok minimum ke semua lokasi yang sudah terbentuk
        |--------------------------------------------------------------------------
        */
        $item->itemStocks()->update([
            'minimum_stock' => $item->minimum_stock,
        ]);

        $item->current_stock = $item
            ->itemStocks()
            ->sum('current_stock');

        $item->save();

        return redirect()
            ->route('items.index')
            ->with('success', 'Barang dan stok minimum semua lokasi berhasil diperbarui.');
    }

    public function destroy(Item $item)
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | Hindari menghapus barang yang sudah digunakan transaksi
            |--------------------------------------------------------------------------
            */
            if (
                $item->stockIns()->exists()
                || $item->requestDetails()->exists()
            ) {
                return redirect()
                    ->route('items.index')
                    ->with(
                        'error',
                        'Barang tidak dapat dihapus karena sudah digunakan pada transaksi.'
                    );
            }

            $item->itemStocks()->delete();
            $item->delete();

            return redirect()
                ->route('items.index')
                ->with('success', 'Barang berhasil dihapus.');
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('items.index')
                ->with(
                    'error',
                    'Barang gagal dihapus karena masih terhubung dengan data lain.'
                );
        }
    }

    private function validateItem(
        Request $request,
        ?Item $item = null
    ): array {
        return $request->validate(
            [
                'category_id' => [
                    'required',
                    'exists:categories,id',
                ],
                'code' => [
                    'required',
                    'string',
                    'max:100',
                    $item
                        ? Rule::unique('items', 'code')
                            ->ignore($item->id)
                        : Rule::unique('items', 'code'),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'type' => [
                    'required',
                    Rule::in(['ATK', 'RTK']),
                ],
                'unit' => [
                    'required',
                    'string',
                    'max:50',
                ],
                'minimum_stock' => [
                    'required',
                    'integer',
                    'min:0',
                ],
                'storage_location' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'category_id.required' => 'Kategori wajib dipilih.',
                'category_id.exists' => 'Kategori tidak valid.',
                'code.required' => 'Kode barang wajib diisi.',
                'code.unique' => 'Kode barang sudah digunakan.',
                'name.required' => 'Nama barang wajib diisi.',
                'type.required' => 'Jenis barang wajib dipilih.',
                'type.in' => 'Jenis barang harus ATK atau RTK.',
                'unit.required' => 'Satuan wajib diisi.',
                'minimum_stock.required' => 'Stok minimum wajib diisi.',
                'minimum_stock.integer' => 'Stok minimum harus berupa angka.',
                'minimum_stock.min' => 'Stok minimum tidak boleh kurang dari 0.',
            ]
        );
    }
}
