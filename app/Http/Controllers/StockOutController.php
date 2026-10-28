<?php

namespace App\Http\Controllers;

use App\Exports\KpiSheetExport;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StockOut;
use App\Models\StockOutItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

/**
 * Pengeluaran barang langsung oleh admin pemegang stok.
 *
 * Untuk permintaan karyawan yang dilayani di tempat: admin mencatat penerima dan
 * barangnya, stok langsung berkurang tanpa pengajuan dan persetujuan. Setiap
 * transaksi tercatat siapa yang mengeluarkan dan siapa penerimanya, dan dapat
 * dicetak sebagai bukti serah terima.
 */
class StockOutController extends Controller
{
    private const FILTERS = ['start_date', 'end_date', 'entity_id', 'warehouse_id', 'category_id', 'search'];

    public function index(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ]);

        $base = StockOut::query()
            ->visibleTo($user)
            ->filtered($request->only(self::FILTERS), $user);

        $stockOuts = (clone $base)
            ->with(['entity', 'warehouse', 'issuer', 'items.item'])
            ->latest('issue_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $totalTransactions = (clone $base)->count();
        $totalUnits = (int) StockOutItem::query()
            ->whereIn('stock_out_id', (clone $base)->select('stock_outs.id'))
            ->sum('quantity');

        return view('stock-outs.index', [
            'stockOuts' => $stockOuts,
            'totalTransactions' => $totalTransactions,
            'totalUnits' => $totalUnits,
            'entities' => $this->entitiesFor($user),
            'warehouses' => $this->warehousesFor($user),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        // Hanya barang bersetok pada lokasi yang boleh diakses yang bisa dikeluarkan.
        $itemStocks = ItemStock::query()
            ->with(['item.category'])
            ->where('current_stock', '>', 0)
            ->whereHas('item', fn ($query) => $query->where('is_active', true))
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->whereIn('entity_id', $user->allowedEntityIds())
                    ->whereIn('warehouse_id', $user->allowedWarehouseIds())
            )
            ->get();

        // Penerima: user aktif ditambah penerima sebelumnya (beserta divisinya),
        // supaya input berulang lebih cepat.
        $previous = StockOut::query()
            ->visibleTo($user)
            ->latest('id')
            ->limit(300)
            ->get(['recipient_name', 'recipient_department'])
            ->unique('recipient_name')
            ->map(fn ($row) => [
                'name' => $row->recipient_name,
                'department' => $row->recipient_department ?? '',
            ])
            ->values();

        $knownNames = $previous->pluck('name');

        $recipients = $previous->concat(
            User::query()
                ->where('is_active', true)
                ->whereNotIn('name', $knownNames)
                ->orderBy('name')
                ->pluck('name')
                ->map(fn ($name) => ['name' => $name, 'department' => ''])
        )->values();

        return view('stock-outs.create', [
            'entities' => $this->entitiesFor($user),
            'warehouses' => $this->warehousesFor($user),
            'itemStocks' => $itemStocks,
            'recipients' => $recipients,
            'currentUser' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate(
            [
                'entity_id' => ['required', 'integer', Rule::exists('entities', 'id')->where('is_active', true)],
                'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
                'issue_date' => ['required', 'date', 'before_or_equal:today'],
                'recipient_name' => ['required', 'string', 'max:150'],
                'recipient_department' => ['nullable', 'string', 'max:150'],
                'note' => ['nullable', 'string', 'max:1000'],
                'items' => ['required', 'array', 'min:1', 'max:100'],
                'items.*.item_id' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::exists('items', 'id')->where('is_active', true),
                ],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
            ],
            [
                'entity_id.required' => 'Entitas wajib dipilih.',
                'warehouse_id.required' => 'Gudang wajib dipilih.',
                'issue_date.required' => 'Tanggal pengeluaran wajib diisi.',
                'issue_date.before_or_equal' => 'Tanggal pengeluaran tidak boleh melewati hari ini.',
                'recipient_name.required' => 'Nama penerima wajib diisi.',
                'items.required' => 'Minimal pilih satu barang.',
                'items.min' => 'Minimal pilih satu barang.',
                'items.max' => 'Maksimal 100 barang sekali simpan.',
                'items.*.item_id.required' => 'Barang wajib dipilih (barang ke-:position).',
                'items.*.item_id.distinct' => 'Barang yang sama tidak boleh dipilih lebih dari satu kali.',
                'items.*.item_id.exists' => 'Barang tidak ditemukan atau sudah nonaktif (barang ke-:position).',
                'items.*.quantity.required' => 'Jumlah wajib diisi (barang ke-:position).',
                'items.*.quantity.integer' => 'Jumlah harus berupa angka bulat (barang ke-:position).',
                'items.*.quantity.min' => 'Jumlah minimal 1 (barang ke-:position).',
            ]
        );

        if (
            ! $user->isSuperAdmin()
            && ! $user->canAccessLocation($validated['entity_id'], $validated['warehouse_id'])
        ) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak memiliki akses ke kombinasi entitas dan gudang tersebut.');
        }

        try {
            $stockOut = DB::transaction(function () use ($validated, $user) {
                $stockOut = StockOut::create([
                    'issue_number' => $this->generateIssueNumber(),
                    'issue_date' => $validated['issue_date'],
                    'entity_id' => $validated['entity_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'user_id' => $user->id,
                    'recipient_name' => trim($validated['recipient_name']),
                    'recipient_department' => filled($validated['recipient_department'] ?? null)
                        ? trim($validated['recipient_department'])
                        : null,
                    'note' => $validated['note'] ?? null,
                ]);

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
                        throw new RuntimeException(
                            'Barang "' . $item->name . '" belum punya stok pada gudang ini.'
                        );
                    }

                    if ($itemStock->current_stock < $quantity) {
                        throw new RuntimeException(
                            'Stok "' . $item->name . '" tidak mencukupi. Stok tersedia: '
                            . $itemStock->current_stock . ' ' . $item->unit . '.'
                        );
                    }

                    StockOutItem::create([
                        'stock_out_id' => $stockOut->id,
                        'item_id' => $item->id,
                        'quantity' => $quantity,
                        'stock_before' => (int) $itemStock->current_stock,
                    ]);

                    $itemStock->decrement('current_stock', $quantity);

                    $item->current_stock = $item->itemStocks()->sum('current_stock');
                    $item->save();
                }

                return $stockOut;
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with(
                'error',
                'Pengeluaran barang gagal disimpan. Silakan coba kembali.'
            );
        }

        return redirect()
            ->route('stock-outs.show', $stockOut)
            ->with('success', 'Pengeluaran barang berhasil dicatat dan stok sudah berkurang.');
    }

    /**
     * Bukti pengeluaran barang (halaman cetak).
     */
    public function show(StockOut $stockOut)
    {
        $user = Auth::user();

        abort_unless(
            $user->isSuperAdmin()
            || $user->canAccessLocation($stockOut->entity_id, $stockOut->warehouse_id),
            403,
            'Anda tidak memiliki akses ke transaksi ini.'
        );

        $stockOut->load(['entity', 'warehouse', 'issuer.position', 'items.item.category']);

        return view('stock-outs.show', [
            'stockOut' => $stockOut,
            'totalQuantity' => (int) $stockOut->items->sum('quantity'),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();

        $rows = StockOut::query()
            ->visibleTo($user)
            ->filtered($request->only(self::FILTERS), $user)
            ->with(['entity', 'warehouse', 'issuer', 'items.item.category'])
            ->latest('issue_date')
            ->latest('id')
            ->get()
            ->flatMap(fn (StockOut $stockOut) => $stockOut->items->map(fn (StockOutItem $line) => [
                $stockOut->issue_number,
                $stockOut->issue_date?->format('d/m/Y'),
                $stockOut->entity?->code ?? '-',
                $stockOut->warehouse?->name ?? '-',
                $stockOut->recipient_name,
                $stockOut->recipient_department ?? '-',
                $line->item?->code ?? '-',
                $line->item?->name ?? '-',
                $line->item?->category?->name ?? '-',
                $line->item?->unit ?? '-',
                $line->quantity,
                $stockOut->issuer?->name ?? '-',
                $stockOut->note ?? '-',
            ]))
            ->values()
            ->all();

        return Excel::download(
            new KpiSheetExport(
                'Barang Keluar Langsung',
                ['Nomor', 'Tanggal', 'Entitas', 'Gudang', 'Penerima', 'Divisi', 'Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Jumlah', 'Dikeluarkan Oleh', 'Catatan'],
                $rows
            ),
            'laporan-barang-keluar-langsung.xlsx'
        );
    }

    private function entitiesFor(User $user)
    {
        return Entity::query()
            ->where('is_active', true)
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->whereIn('id', $user->allowedEntityIds()))
            ->orderBy('code')
            ->get();
    }

    private function warehousesFor(User $user)
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->whereIn('id', $user->allowedWarehouseIds()))
            ->orderBy('name')
            ->get();
    }

    private function generateIssueNumber(): string
    {
        $prefix = 'SKO-' . now()->format('Ymd') . '-';

        $last = StockOut::query()
            ->where('issue_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('issue_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
