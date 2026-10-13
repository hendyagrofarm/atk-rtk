<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StockAudit;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockAuditController extends Controller
{
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

        $query = StockAudit::query()
            ->with(['entity', 'warehouse', 'item.category', 'user']);

        if (! $isSuperAdmin) {
            $query
                ->whereIn('entity_id', $allowedEntityIds)
                ->whereIn('warehouse_id', $allowedWarehouseIds);
        }

        $query
            ->when(
                $request->filled('start_date'),
                fn ($q) => $q->whereDate(
                    'audit_date',
                    '>=',
                    $request->start_date
                )
            )
            ->when(
                $request->filled('end_date'),
                fn ($q) => $q->whereDate(
                    'audit_date',
                    '<=',
                    $request->end_date
                )
            )
            ->when(
                $request->filled('entity_id'),
                fn ($q) => $q->where(
                    'entity_id',
                    $request->entity_id
                )
            )
            ->when(
                $request->filled('warehouse_id'),
                fn ($q) => $q->where(
                    'warehouse_id',
                    $request->warehouse_id
                )
            )
            ->when(
                $request->filled('search'),
                function ($q) use ($request) {
                    $search = trim((string) $request->search);

                    $q->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where(
                                'audit_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhereHas(
                                'item',
                                function ($itemQuery) use ($search) {
                                    $itemQuery
                                        ->where(
                                            'code',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        );
                                }
                            )
                            ->orWhere(
                                'reason',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            );

        $audits = $query
            ->latest('audit_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summaryQuery = StockAudit::query();

        if (! $isSuperAdmin) {
            $summaryQuery
                ->whereIn('entity_id', $allowedEntityIds)
                ->whereIn('warehouse_id', $allowedWarehouseIds);
        }

        $totalAudits = (clone $summaryQuery)->count();
        $totalPositive = (clone $summaryQuery)
            ->where('difference', '>', 0)
            ->sum('difference');
        $totalNegative = abs((clone $summaryQuery)
            ->where('difference', '<', 0)
            ->sum('difference'));

        return view('stock-audits.index', compact(
            'audits',
            'entities',
            'warehouses',
            'totalAudits',
            'totalPositive',
            'totalNegative'
        ));
    }

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

        $itemStocks = ItemStock::query()
            ->with(['entity', 'warehouse', 'item.category'])
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->orderBy('entity_id')
            ->orderBy('warehouse_id')
            ->orderBy('item_id')
            ->get();

        return view('stock-audits.create', compact(
            'entities',
            'warehouses',
            'itemStocks'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'audit_date' => ['required', 'date'],
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
            'item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where('is_active', true),
            ],
            'physical_stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'reason' => [
                'required',
                'string',
                'max:255',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        abort_unless(
            $user->canAccessLocation(
                $validated['entity_id'],
                $validated['warehouse_id']
            ),
            403,
            'Anda tidak memiliki akses ke lokasi tersebut.'
        );

        DB::transaction(function () use ($validated, $user) {
            $itemStock = ItemStock::query()
                ->where('entity_id', $validated['entity_id'])
                ->where('warehouse_id', $validated['warehouse_id'])
                ->where('item_id', $validated['item_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $systemStock = (int) $itemStock->current_stock;
            $physicalStock = (int) $validated['physical_stock'];
            $difference = $physicalStock - $systemStock;

            StockAudit::create([
                'audit_number' => $this->generateAuditNumber(),
                'audit_date' => $validated['audit_date'],
                'entity_id' => $validated['entity_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'item_id' => $validated['item_id'],
                'user_id' => $user->id,
                'system_stock' => $systemStock,
                'physical_stock' => $physicalStock,
                'difference' => $difference,
                'reason' => $validated['reason'],
                'note' => $validated['note'] ?? null,
            ]);

            $itemStock->update([
                'current_stock' => $physicalStock,
            ]);

            $item = Item::query()
                ->lockForUpdate()
                ->find($validated['item_id']);

            if ($item) {
                $item->current_stock = $item
                    ->itemStocks()
                    ->sum('current_stock');

                $item->save();
            }
        });

        return redirect()
            ->route('stock-audits.index')
            ->with(
                'success',
                'Audit stok berhasil disimpan dan stok sistem telah disesuaikan.'
            );
    }

    private function generateAuditNumber(): string
    {
        $prefix = 'AUD-' . now()->format('Ymd') . '-';

        $lastNumber = StockAudit::query()
            ->whereDate('created_at', now()->toDateString())
            ->lockForUpdate()
            ->latest('id')
            ->value('audit_number');

        $sequence = 1;

        if ($lastNumber) {
            $lastSequence = (int) substr($lastNumber, -4);
            $sequence = $lastSequence + 1;
        }

        return $prefix . str_pad(
            (string) $sequence,
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}
