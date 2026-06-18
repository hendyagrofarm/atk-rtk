<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Request as Pengajuan;
use App\Models\RequestDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    /**
     * Menampilkan daftar pengajuan milik staff yang sedang login.
     */
    public function index()
    {
        $requests = Pengajuan::with(['entity'])
            ->withCount('details')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('staff.requests.index', compact('requests'));
    }

    /**
     * Menampilkan form buat pengajuan baru.
     */
    public function create()
    {
        $entities = Entity::where('is_active', true)
            ->orderBy('code')
            ->get();

        $items = Item::with(['category', 'itemStocks.entity'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('staff.requests.create', compact('items', 'entities'));
    }

    /**
     * Menyimpan pengajuan baru dari staff.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'entity_id' => ['required', 'exists:entities,id'],
            'note' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ], [
            'entity_id.required' => 'Entitas wajib dipilih.',
            'entity_id.exists' => 'Entitas tidak valid.',

            'items.required' => 'Minimal pilih satu barang.',
            'items.array' => 'Format barang tidak valid.',
            'items.min' => 'Minimal pilih satu barang.',

            'items.*.item_id.required' => 'Barang wajib dipilih.',
            'items.*.item_id.integer' => 'Barang tidak valid.',
            'items.*.item_id.exists' => 'Barang yang dipilih tidak ditemukan.',
            'items.*.item_id.distinct' => 'Barang tidak boleh dipilih lebih dari satu kali dalam satu pengajuan.',

            'items.*.quantity.required' => 'Jumlah diminta wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah diminta wajib berupa angka.',
            'items.*.quantity.min' => 'Jumlah diminta harus lebih dari 0.',

            'items.*.note.string' => 'Keterangan barang harus berupa teks.',
            'items.*.note.max' => 'Keterangan barang maksimal 255 karakter.',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $requestNumber = $this->generateRequestNumber();

                $pengajuan = Pengajuan::create([
                    'request_number' => $requestNumber,
                    'request_date' => now()->toDateString(),
                    'user_id' => Auth::id(),
                    'entity_id' => $validated['entity_id'],
                    'approver_id' => null,
                    'status' => 'pending',
                    'note' => $validated['note'] ?? null,
                    'approver_note' => null,
                    'approved_at' => null,
                ]);

                foreach ($validated['items'] as $item) {
                    RequestDetail::create([
                        'request_id' => $pengajuan->id,
                        'item_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                        'approved_quantity' => 0,
                        'note' => $item['note'] ?? null,
                    ]);
                }
            });

            return redirect()
                ->route('staff.requests.index')
                ->with('success', 'Pengajuan berhasil dibuat dan menunggu persetujuan.');

        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Pengajuan gagal dibuat: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan detail pengajuan milik staff yang sedang login.
     */
    public function show($id)
    {
        $request = Pengajuan::with([
                'entity',
                'details.item.category',
                'details.item.itemStocks.entity',
                'user',
                'approver',
            ])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('staff.requests.show', compact('request'));
    }

    /**
     * Membuat nomor pengajuan otomatis.
     *
     * Format:
     * PGJ-20260505-0001
     */
    private function generateRequestNumber(): string
    {
        $date = now()->format('Ymd');

        $lastRequest = Pengajuan::where('request_number', 'like', 'PGJ-' . $date . '-%')
            ->lockForUpdate()
            ->orderByDesc('request_number')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'PGJ-' . $date . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}