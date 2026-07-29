<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\Item;
use App\Models\Request as Pengajuan;
use App\Models\RequestDetail;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class RequestController extends Controller
{
    public function index()
    {
        $requests = Pengajuan::query()
            ->with([
                'entity',
                'warehouse',
            ])
            ->withCount('details')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('staff.requests.index', compact('requests'));
    }

    /**
     * Form pengajuan barang dari stok.
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
            ->with([
                'category',
                'itemStocks' => function ($query) use (
                    $isSuperAdmin,
                    $allowedEntityIds,
                    $allowedWarehouseIds
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
                        );
                },
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('staff.requests.create', compact(
            'items',
            'entities',
            'warehouses',
            'user'
        ));
    }

    /**
     * Simpan pengajuan barang dari stok.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $validated = $request->validate([
            'entity_id' => [
                'required',
                'integer',
                Rule::exists('entities', 'id')->where('is_active', true),
            ],
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('is_active', true),
            ],
            'note' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where('is_active', true),
                'distinct',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'items.*.note' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'entity_id.required' => 'Entitas wajib dipilih.',
            'entity_id.exists' => 'Entitas tidak valid atau sudah nonaktif.',
            'warehouse_id.required' => 'Gudang wajib dipilih.',
            'warehouse_id.exists' => 'Gudang tidak valid atau sudah nonaktif.',
            'items.required' => 'Minimal pilih satu barang.',
            'items.min' => 'Minimal pilih satu barang.',
            'items.*.item_id.required' => 'Barang wajib dipilih.',
            'items.*.item_id.exists' => 'Barang tidak ditemukan atau sudah nonaktif.',
            'items.*.item_id.distinct' => 'Barang tidak boleh dipilih lebih dari satu kali.',
            'items.*.quantity.required' => 'Jumlah diminta wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah diminta harus berupa angka bulat.',
            'items.*.quantity.min' => 'Jumlah diminta minimal 1.',
        ]);

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

        /*
        |--------------------------------------------------------------------------
        | Pastikan setiap barang memiliki stok pada lokasi yang dipilih
        |--------------------------------------------------------------------------
        */
        foreach ($validated['items'] as $row) {
            $stockExists = \App\Models\ItemStock::query()
                ->where('entity_id', $validated['entity_id'])
                ->where('warehouse_id', $validated['warehouse_id'])
                ->where('item_id', $row['item_id'])
                ->exists();

            if (! $stockExists) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Salah satu barang belum mempunyai stok pada entitas dan gudang yang dipilih.'
                    );
            }
        }

        try {
            DB::transaction(function () use ($validated, $user) {
                $pengajuan = Pengajuan::create([
                    'request_number' => $this->generateRequestNumber(),
                    'request_date' => now()->toDateString(),
                    'request_type' => 'stock',
                    'user_id' => $user->id,
                    'entity_id' => $validated['entity_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'approver_id' => null,
                    'status' => 'pending',
                    'note' => $validated['note'] ?? null,
                    'estimated_total' => null,
                    'approver_note' => null,
                    'approved_at' => null,
                ]);

                foreach ($validated['items'] as $item) {
                    RequestDetail::create([
                        'request_id' => $pengajuan->id,
                        'item_id' => $item['item_id'],
                        'custom_item_name' => null,
                        'custom_category' => null,
                        'specification' => null,
                        'quantity' => $item['quantity'],
                        'approved_quantity' => 0,
                        'unit' => null,
                        'estimated_price' => null,
                        'product_url' => null,
                        'note' => $item['note'] ?? null,
                    ]);
                }
            });

            return redirect()
                ->route('staff.requests.index')
                ->with(
                    'success',
                    'Pengajuan stok berhasil dibuat untuk lokasi yang dipilih.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Pengajuan gagal dibuat: ' . $e->getMessage()
                );
        }
    }

    public function createCustom()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $entities = Entity::query()
            ->where('is_active', true)
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query->whereIn(
                    'id',
                    $user->allowedEntityIds()
                )
            )
            ->orderBy('code')
            ->get();

        return view(
            'staff.requests.create-custom',
            compact('entities')
        );
    }

    public function storeCustom(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $validated = $request->validate([
            'entity_id' => [
                'required',
                'exists:entities,id',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.custom_item_name' => [
                'required',
                'string',
                'max:255',
            ],
            'items.*.custom_category' => [
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.specification' => [
                'nullable',
                'string',
                'max:3000',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'items.*.unit' => [
                'required',
                'string',
                'max:50',
            ],
            'items.*.estimated_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
            'items.*.product_url' => [
                'nullable',
                'url',
                'max:1000',
            ],
            'items.*.note' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        if (
            ! $isSuperAdmin
            && ! $user->canAccessEntity($validated['entity_id'])
        ) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak memiliki akses ke entitas tersebut.');
        }

        try {
            DB::transaction(function () use ($validated, $user) {
                $estimatedTotal = collect($validated['items'])
                    ->sum(function ($item) {
                        return (int) $item['quantity']
                            * (float) ($item['estimated_price'] ?? 0);
                    });

                $pengajuan = Pengajuan::create([
                    'request_number' => $this->generateRequestNumber(),
                    'request_date' => now()->toDateString(),
                    'request_type' => 'custom',
                    'user_id' => $user->id,
                    'entity_id' => $validated['entity_id'],
                    /*
                    |--------------------------------------------------------------------------
                    | Pengajuan custom tidak mengurangi stok
                    |--------------------------------------------------------------------------
                    | Gunakan gudang default agar data lama tetap kompatibel.
                    */
                    'warehouse_id' => $user->warehouse_id,
                    'approver_id' => null,
                    'status' => 'pending',
                    'note' => $validated['note'] ?? null,
                    'estimated_total' => $estimatedTotal,
                    'approver_note' => null,
                    'approved_at' => null,
                ]);

                foreach ($validated['items'] as $item) {
                    RequestDetail::create([
                        'request_id' => $pengajuan->id,
                        'item_id' => null,
                        'custom_item_name' => $item['custom_item_name'],
                        'custom_category' => $item['custom_category'] ?? null,
                        'specification' => $item['specification'] ?? null,
                        'quantity' => $item['quantity'],
                        'approved_quantity' => 0,
                        'unit' => $item['unit'],
                        'estimated_price' => $item['estimated_price'] ?? null,
                        'product_url' => $item['product_url'] ?? null,
                        'note' => $item['note'] ?? null,
                    ]);
                }
            });

            return redirect()
                ->route('staff.requests.index')
                ->with(
                    'success',
                    'Pengajuan barang custom berhasil dibuat.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Pengajuan custom gagal dibuat: ' . $e->getMessage()
                );
        }
    }

    public function show($id)
    {
        $request = Pengajuan::query()
            ->with([
                'entity',
                'warehouse',
                'details.item.category',
                'details.item.itemStocks.entity',
                'details.item.itemStocks.warehouse',
                'user',
                'approver',
            ])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view(
            'staff.requests.show',
            compact('request')
        );
    }

    private function generateRequestNumber(): string
    {
        $date = now()->format('Ymd');

        $lastRequest = Pengajuan::query()
            ->where(
                'request_number',
                'like',
                'PGJ-' . $date . '-%'
            )
            ->lockForUpdate()
            ->orderByDesc('request_number')
            ->first();

        $nextNumber = $lastRequest
            ? ((int) substr($lastRequest->request_number, -4)) + 1
            : 1;

        return 'PGJ-'
            . $date
            . '-'
            . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
