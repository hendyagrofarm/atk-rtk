<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockInController extends Controller
{
    public function index(Request $request)
    {
        $entities = Entity::where('is_active', true)
            ->orderBy('code')
            ->get();

        $items = Item::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $stockIns = StockIn::with([
                'entity',
                'item.category',
                'item.itemStocks.entity',
                'user',
            ])
            ->when($request->filled('start_date'), function ($query) use ($request) {
                $query->whereDate('date', '>=', $request->start_date);
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                $query->whereDate('date', '<=', $request->end_date);
            })
            ->when($request->filled('entity_id'), function ($query) use ($request) {
                $query->where('entity_id', $request->entity_id);
            })
            ->when($request->filled('item_id'), function ($query) use ($request) {
                $query->where('item_id', $request->item_id);
            })
            ->latest('date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('stock-ins.index', compact(
            'stockIns',
            'items',
            'entities'
        ));
    }

    public function create()
    {
        $entities = Entity::where('is_active', true)
            ->orderBy('code')
            ->get();

        $items = Item::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock-ins.create', compact('items', 'entities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entity_id' => ['required', 'exists:entities,id'],
            'item_id' => ['required', 'exists:items,id'],
            'date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ], [
            'entity_id.required' => 'Entitas wajib dipilih.',
            'entity_id.exists' => 'Entitas yang dipilih tidak valid.',

            'item_id.required' => 'Barang wajib dipilih.',
            'item_id.exists' => 'Barang yang dipilih tidak valid.',

            'date.required' => 'Tanggal masuk wajib diisi.',
            'date.date' => 'Tanggal masuk tidak valid.',

            'quantity.required' => 'Jumlah masuk wajib diisi.',
            'quantity.integer' => 'Jumlah masuk harus berupa angka.',
            'quantity.min' => 'Jumlah masuk minimal 1.',

            'supplier.max' => 'Nama supplier maksimal 255 karakter.',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $item = Item::lockForUpdate()->findOrFail($validated['item_id']);

                $itemStock = ItemStock::where('entity_id', $validated['entity_id'])
                    ->where('item_id', $validated['item_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $itemStock) {
                    $itemStock = ItemStock::create([
                        'entity_id' => $validated['entity_id'],
                        'item_id' => $validated['item_id'],
                        'current_stock' => 0,
                        'minimum_stock' => $item->minimum_stock ?? 0,
                    ]);
                }

                StockIn::create([
                    'entity_id' => $validated['entity_id'],
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'quantity' => $validated['quantity'],
                    'date' => $validated['date'],
                    'supplier' => $validated['supplier'] ?? null,
                    'note' => $validated['note'] ?? null,
                ]);

                $itemStock->current_stock = $itemStock->current_stock + $validated['quantity'];
                $itemStock->save();

                /*
                |--------------------------------------------------------------------------
                | Sinkron stok utama item
                |--------------------------------------------------------------------------
                | Untuk sementara current_stock di tabel items tetap diisi total stok semua entitas,
                | supaya fitur lama yang masih membaca items.current_stock tidak langsung rusak.
                */
                $item->current_stock = $item->itemStocks()->sum('current_stock');
                $item->save();
            });

            return redirect()
                ->route('stock-ins.index')
                ->with('success', 'Stok masuk berhasil disimpan sesuai entitas yang dipilih.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan stok masuk. Silakan coba lagi.');
        }
    }
}