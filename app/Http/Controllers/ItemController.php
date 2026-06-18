<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $entityId = $request->input('entity_id');
        $status = $request->input('status');

        $items = Item::with(['category', 'itemStocks.entity'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            })
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($entityId, function ($query, $entityId) {
                $query->whereHas('itemStocks', function ($q) use ($entityId) {
                    $q->where('entity_id', $entityId);
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        $entities = Entity::where('is_active', true)
            ->orderBy('code')
            ->get();

        $selectedEntity = null;

        if ($entityId) {
            $selectedEntity = $entities->firstWhere('id', (int) $entityId);
        }

        return view('items.index', compact(
            'items',
            'categories',
            'entities',
            'selectedEntity',
            'search',
            'categoryId',
            'entityId',
            'status'
        ));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateItem($request);

        $validated['is_active'] = $request->has('is_active');

        $item = Item::create($validated);

        $this->syncItemStocks($item, true);

        return redirect()
            ->route('items.index')
            ->with('success', 'Barang berhasil ditambahkan dan stok ANR/MAA berhasil dibuat.');
    }

    public function show(Item $item)
    {
        return redirect()->route('items.index');
    }

    public function edit(Item $item)
    {
        $categories = Category::orderBy('name')->get();

        return view('items.edit', compact('item', 'categories'));
    }

    public function update(Request $request, Item $item)
    {
        $validated = $this->validateItem($request, $item);

        $validated['is_active'] = $request->has('is_active');

        $item->update($validated);

        $this->syncItemStocks($item, false);

        return redirect()
            ->route('items.index')
            ->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(Item $item)
    {
        try {
            $item->itemStocks()->delete();

            $item->delete();

            return redirect()
                ->route('items.index')
                ->with('success', 'Barang berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()
                ->route('items.index')
                ->with('error', 'Barang gagal dihapus. Kemungkinan barang sudah digunakan di data transaksi lain.');
        }
    }

    private function validateItem(Request $request, ?Item $item = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => [
                'required',
                'string',
                'max:100',
                $item
                    ? Rule::unique('items', 'code')->ignore($item->id)
                    : Rule::unique('items', 'code'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['ATK', 'RTK'])],
            'unit' => ['required', 'string', 'max:50'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'current_stock' => ['required', 'integer', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',

            'code.required' => 'Kode barang wajib diisi.',
            'code.unique' => 'Kode barang sudah digunakan.',
            'code.max' => 'Kode barang maksimal 100 karakter.',

            'name.required' => 'Nama barang wajib diisi.',
            'name.max' => 'Nama barang maksimal 255 karakter.',

            'type.required' => 'Jenis barang wajib dipilih.',
            'type.in' => 'Jenis barang harus ATK atau RTK.',

            'unit.required' => 'Satuan wajib diisi.',
            'unit.max' => 'Satuan maksimal 50 karakter.',

            'minimum_stock.required' => 'Stok minimum wajib diisi.',
            'minimum_stock.integer' => 'Stok minimum harus berupa angka.',
            'minimum_stock.min' => 'Stok minimum tidak boleh kurang dari 0.',

            'current_stock.required' => 'Stok saat ini wajib diisi.',
            'current_stock.integer' => 'Stok saat ini harus berupa angka.',
            'current_stock.min' => 'Stok saat ini tidak boleh kurang dari 0.',
        ]);
    }

    private function syncItemStocks(Item $item, bool $isNewItem = false): void
    {
        $entities = Entity::where('is_active', true)->get();

        foreach ($entities as $entity) {
            $data = [
                'minimum_stock' => $item->minimum_stock ?? 0,
            ];

            if ($isNewItem) {
                $data['current_stock'] = 0;
            }

            ItemStock::updateOrCreate(
                [
                    'entity_id' => $entity->id,
                    'item_id' => $item->id,
                ],
                $data
            );
        }
    }
}