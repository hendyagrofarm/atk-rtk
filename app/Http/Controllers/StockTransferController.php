<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $entities = Entity::active()
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedEntityIds))
            ->orderBy('code')->get();

        $warehouses = Warehouse::active()
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedWarehouseIds))
            ->ordered()->get();

        $items = Item::where('is_active', true)->orderBy('name')->get();

        $query = StockTransfer::with([
            'entity',
            'sourceWarehouse',
            'destinationWarehouse',
            'item.category',
            'user',
        ]);

        if (! $isSuperAdmin) {
            $query->whereIn('entity_id', $allowedEntityIds)
                ->whereIn('source_warehouse_id', $allowedWarehouseIds)
                ->whereIn('destination_warehouse_id', $allowedWarehouseIds);
        }

        $query
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('transfer_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('transfer_date', '<=', $request->end_date))
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->when($request->filled('entity_id'), function ($q) use ($request, $isSuperAdmin, $allowedEntityIds) {
                $id = (int) $request->entity_id;
                if ($isSuperAdmin || $allowedEntityIds->contains($id)) {
                    $q->where('entity_id', $id);
                }
            })
            ->when($request->filled('source_warehouse_id'), function ($q) use ($request, $isSuperAdmin, $allowedWarehouseIds) {
                $id = (int) $request->source_warehouse_id;
                if ($isSuperAdmin || $allowedWarehouseIds->contains($id)) {
                    $q->where('source_warehouse_id', $id);
                }
            })
            ->when($request->filled('destination_warehouse_id'), function ($q) use ($request, $isSuperAdmin, $allowedWarehouseIds) {
                $id = (int) $request->destination_warehouse_id;
                if ($isSuperAdmin || $allowedWarehouseIds->contains($id)) {
                    $q->where('destination_warehouse_id', $id);
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('transfer_number', 'like', "%{$search}%")
                        ->orWhereHas('item', function ($itemQuery) use ($search) {
                            $itemQuery->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            });

        $stockTransfers = $query
            ->latest('transfer_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('stock-transfers.index', compact(
            'stockTransfers',
            'entities',
            'warehouses',
            'items',
            'isSuperAdmin'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $entities = Entity::active()
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedEntityIds))
            ->orderBy('code')->get();

        $warehouses = Warehouse::active()
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $allowedWarehouseIds))
            ->ordered()->get();

        $itemStocks = ItemStock::with(['entity', 'warehouse', 'item.category'])
            ->where('current_stock', '>', 0)
            ->whereHas('item', fn ($q) => $q->where('is_active', true))
            ->when(! $isSuperAdmin, fn ($q) => $q
                ->whereIn('entity_id', $allowedEntityIds)
                ->whereIn('warehouse_id', $allowedWarehouseIds))
            ->get()
            ->sortBy(fn ($stock) =>
                ($stock->entity?->code ?? '') . '|' .
                ($stock->warehouse?->name ?? '') . '|' .
                ($stock->item?->name ?? '')
            )->values();

        return view('stock-transfers.create', compact(
            'entities',
            'warehouses',
            'itemStocks',
            'user',
            'isSuperAdmin'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $validated = $request->validate([
            'entity_id' => ['required', 'integer', Rule::exists('entities', 'id')->where('is_active', true)],
            'source_warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'destination_warehouse_id' => [
                'required',
                'integer',
                'different:source_warehouse_id',
                Rule::exists('warehouses', 'id')->where('is_active', true),
            ],
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1'],
            'transfer_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [
            'entity_id.required' => 'Entitas wajib dipilih.',
            'source_warehouse_id.required' => 'Gudang asal wajib dipilih.',
            'destination_warehouse_id.required' => 'Gudang tujuan wajib dipilih.',
            'destination_warehouse_id.different' => 'Gudang tujuan harus berbeda dari gudang asal.',
            'item_id.required' => 'Barang wajib dipilih.',
            'quantity.required' => 'Jumlah mutasi wajib diisi.',
            'quantity.min' => 'Jumlah mutasi minimal 1.',
            'transfer_date.required' => 'Tanggal mutasi wajib diisi.',
        ]);

        if (! $isSuperAdmin) {
            $canAccessSource = $user->canAccessLocation(
                $validated['entity_id'],
                $validated['source_warehouse_id']
            );

            $canAccessDestination = $user->canAccessLocation(
                $validated['entity_id'],
                $validated['destination_warehouse_id']
            );

            if (! $canAccessSource || ! $canAccessDestination) {
                return back()->withInput()->with(
                    'error',
                    'Anda tidak memiliki akses ke gudang asal atau gudang tujuan.'
                );
            }
        }

        try {
            DB::transaction(function () use ($validated, $user) {
                $sourceStock = ItemStock::where('entity_id', $validated['entity_id'])
                    ->where('warehouse_id', $validated['source_warehouse_id'])
                    ->where('item_id', $validated['item_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $sourceStock) {
                    throw new RuntimeException('Stok barang pada gudang asal tidak ditemukan.');
                }

                if ($sourceStock->current_stock < $validated['quantity']) {
                    throw new RuntimeException(
                        'Stok gudang asal tidak mencukupi. Stok tersedia: ' .
                        $sourceStock->current_stock . '.'
                    );
                }

                $destinationStock = ItemStock::where('entity_id', $validated['entity_id'])
                    ->where('warehouse_id', $validated['destination_warehouse_id'])
                    ->where('item_id', $validated['item_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $destinationStock) {
                    $destinationStock = ItemStock::create([
                        'entity_id' => $validated['entity_id'],
                        'warehouse_id' => $validated['destination_warehouse_id'],
                        'item_id' => $validated['item_id'],
                        'current_stock' => 0,
                        'minimum_stock' => $sourceStock->minimum_stock,
                    ]);
                }

                $sourceStock->decrement('current_stock', $validated['quantity']);
                $destinationStock->increment('current_stock', $validated['quantity']);

                StockTransfer::create([
                    'transfer_number' => $this->generateTransferNumber(),
                    'entity_id' => $validated['entity_id'],
                    'source_warehouse_id' => $validated['source_warehouse_id'],
                    'destination_warehouse_id' => $validated['destination_warehouse_id'],
                    'item_id' => $validated['item_id'],
                    'user_id' => $user->id,
                    'quantity' => $validated['quantity'],
                    'transfer_date' => $validated['transfer_date'],
                    'note' => $validated['note'] ?? null,
                ]);

                $item = Item::lockForUpdate()->find($validated['item_id']);

                if ($item) {
                    $item->current_stock = $item->itemStocks()->sum('current_stock');
                    $item->save();
                }
            });

            return redirect()
                ->route('stock-transfers.index')
                ->with('success', 'Mutasi stok berhasil disimpan.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with(
                'error',
                'Mutasi stok gagal disimpan. Silakan coba kembali.'
            );
        }
    }

    private function generateTransferNumber(): string
    {
        $prefix = 'MTS-' . now()->format('Ymd') . '-';

        $lastNumber = StockTransfer::where('transfer_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('transfer_number');

        $sequence = $lastNumber
            ? ((int) substr($lastNumber, -4)) + 1
            : 1;

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
