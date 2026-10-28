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
use App\Support\ApprovalChain;
use App\Support\Fulfillment;
use App\Support\SignatureStore;
use App\Support\RequestTimeline;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RequestController extends Controller
{
    public function index()
    {
        $requests = Pengajuan::query()
            ->with([
                'entity',
                'warehouse',
                'approvals',
            ])
            ->withCount('details')
            ->mine(Auth::user())
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
        | Jumlah pengajuan tidak dibatasi stok lokasi
        |--------------------------------------------------------------------------
        | Barang kosong atau belum punya baris stok di lokasi tetap boleh
        | diajukan. Kekurangannya dicatat pada request_details untuk laporan KPI,
        | dan approver hanya dapat menyetujui sebesar stok yang tersedia.
        |--------------------------------------------------------------------------
        */
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
                    /*
                     * Catat stok lokasi saat pengajuan dibuat untuk laporan KPI.
                     * Kekurangan = jumlah diminta yang tidak tertutup stok tersedia.
                     */
                    $stockAtRequest = (int) \App\Models\ItemStock::query()
                        ->where('entity_id', $validated['entity_id'])
                        ->where('warehouse_id', $validated['warehouse_id'])
                        ->where('item_id', $item['item_id'])
                        ->value('current_stock');

                    RequestDetail::create([
                        'request_id' => $pengajuan->id,
                        'item_id' => $item['item_id'],
                        'custom_item_name' => null,
                        'custom_category' => null,
                        'specification' => null,
                        'quantity' => $item['quantity'],
                        'stock_at_request' => $stockAtRequest,
                        'shortage_quantity' => max(0, (int) $item['quantity'] - $stockAtRequest),
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
                    'Pengajuan gagal dibuat. Silakan periksa data dan coba kembali.'
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

        // Admin dan super admin boleh mengisi FPB atas nama PIC dari divisi lain.
        $canSetPic = in_array($user->role, ['super_admin', 'admin'], true);

        // Calon PIC (admin mengisi atas nama orang lain): semua user aktif. PIC yang dipilih dari
        // sistem otomatis membawa tanda tangan digitalnya ke FPB.
        $picCandidates = \App\Models\User::query()
            ->with('position')
            ->where('is_active', true)
            // Akun Super Admin tidak ditampilkan kepada admin biasa.
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('role', '!=', 'super_admin'))
            ->orderBy('name')
            ->get()
            ->map(fn ($person) => [
                'id' => $person->id,
                'name' => $person->name,
                'position' => $person->position?->name ?? '',
                'has_signature' => SignatureStore::userHasSignature($person),
            ])
            ->values();

        $ownHasSignature = SignatureStore::userHasSignature($user);

        // Tahap tetap setelah atasan langsung, untuk ditampilkan sebagai gambaran alur.
        $fixedSteps = \App\Models\ApprovalStep::query()
            ->active()
            ->ordered()
            ->with('position')
            ->get()
            ->filter(fn ($step) => $step->position !== null)
            ->values();

        // Calon atasan langsung: user aktif selain pembuat. Atasan yang dipilih menyetujui
        // secara digital, sehingga tanda tangannya perlu tersedia.
        $superiorCandidates = \App\Models\User::query()
            ->with('position')
            ->where('is_active', true)
            // Akun Super Admin tidak ditampilkan kepada admin biasa.
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('role', '!=', 'super_admin'))
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get()
            ->map(fn ($person) => [
                'id' => $person->id,
                'name' => $person->name,
                'position' => $person->position?->name ?? '',
                'has_signature' => SignatureStore::userHasSignature($person),
            ])
            ->values();

        return view(
            'staff.requests.create-custom',
            compact('entities', 'user', 'canSetPic', 'picCandidates', 'superiorCandidates', 'ownHasSignature', 'fixedSteps')
        );
    }

    public function storeCustom(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $canSetPic = in_array($user->role, ['super_admin', 'admin'], true);

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
            'department' => [
                'required',
                'string',
                'max:150',
            ],
            'pic_name' => [
                Rule::requiredIf($canSetPic && ! $request->filled('pic_user_id')),
                'nullable',
                'string',
                'max:150',
            ],
            'pic_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
            'pic_signature' => ['nullable', 'file', 'mimes:png', 'max:' . SignatureStore::MAX_KILOBYTES],
            'pic_position' => ['nullable', 'string', 'max:150'],
            'superior_name' => ['nullable', 'string', 'max:150'],
            'superior_position' => ['nullable', 'string', 'max:150'],
            'superior_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
                Rule::notIn([$user->id]),
            ],
            'superior_signature' => ['nullable', 'file', 'mimes:png', 'max:' . SignatureStore::MAX_KILOBYTES],
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
            'items.*.budget_type' => [
                'required',
                Rule::in(['OPEX', 'CAPEX']),
            ],
            'items.*.specification' => [
                'nullable',
                'string',
                'max:3000',
            ],
            'items.*.brand_type' => [
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.usage_purpose' => [
                'nullable',
                'string',
                'max:1000',
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

        // Atasan langsung dari user sistem menyetujui secara digital; nama dan jabatannya
        // mengikuti data user, bukan isian manual.
        $superior = filled($validated['superior_user_id'] ?? null)
            ? \App\Models\User::with('position')->find($validated['superior_user_id'])
            : null;

        // PIC: staff selalu dirinya sendiri; admin boleh memilih user sistem (membawa tanda
        // tangan digital) atau mengisi manual (tanda tangan basah).
        $picUser = $canSetPic
            ? (filled($validated['pic_user_id'] ?? null)
                ? \App\Models\User::with('position')->find($validated['pic_user_id'])
                : null)
            : $user;

        if ($picUser && $superior && $picUser->id === $superior->id) {
            throw ValidationException::withMessages([
                'superior_user_id' => 'Atasan langsung tidak boleh orang yang sama dengan PIC.',
            ]);
        }

        $signatureFile = $canSetPic && $superior ? $request->file('superior_signature') : null;
        $picSignatureFile = $canSetPic && $picUser ? $request->file('pic_signature') : null;

        foreach (['superior_signature' => $signatureFile, 'pic_signature' => $picSignatureFile] as $field => $file) {
            if ($file) {
                try {
                    SignatureStore::assertValidPng($file);
                } catch (RuntimeException $e) {
                    throw ValidationException::withMessages([$field => $e->getMessage()]);
                }
            }
        }

        try {
            DB::transaction(function () use ($validated, $user, $canSetPic, $superior, $signatureFile, $picUser, $picSignatureFile) {
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
                    'department' => $validated['department'],
                    // Staff selalu menjadi PIC dirinya sendiri; admin dapat menentukan PIC.
                    'pic_user_id' => $picUser?->id,
                    'pic_name' => $picUser ? $picUser->name : trim($validated['pic_name']),
                    'pic_position' => $picUser
                        ? $picUser->position?->name
                        : (filled($validated['pic_position'] ?? null) ? trim($validated['pic_position']) : null),
                    'superior_user_id' => $superior?->id,
                    'superior_name' => $superior
                        ? $superior->name
                        : (filled($validated['superior_name'] ?? null) ? trim($validated['superior_name']) : null),
                    'superior_position' => $superior
                        ? $superior->position?->name
                        : (filled($validated['superior_position'] ?? null) ? trim($validated['superior_position']) : null),
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
                        'budget_type' => $item['budget_type'],
                        'specification' => $item['specification'] ?? null,
                        'brand_type' => $item['brand_type'] ?? null,
                        'usage_purpose' => $item['usage_purpose'] ?? null,
                        'quantity' => $item['quantity'],
                        'approved_quantity' => 0,
                        'unit' => $item['unit'],
                        'estimated_price' => $item['estimated_price'] ?? null,
                        'product_url' => $item['product_url'] ?? null,
                        'note' => $item['note'] ?? null,
                    ]);
                }

                // Admin dapat mengunggah tanda tangan atasan langsung saat membuat FPB.
                if ($signatureFile && $superior) {
                    SignatureStore::store($superior, $signatureFile);
                }

                if ($picSignatureFile && $picUser) {
                    SignatureStore::store($picUser, $picSignatureFile);
                }

                // Tanda tangan digital PIC disalin saat FPB dibuat.
                SignatureStore::snapshotForRequest($pengajuan, $picUser);

                ApprovalChain::initialize($pengajuan);
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
                    'Pengajuan custom gagal dibuat. Silakan periksa data dan coba kembali.'
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
                'user.position',
                'approver.position',
                'approvals.user.position',
                'fulfillments.user',
                'picUser',
            ])
            ->mine(Auth::user())
            ->findOrFail($id);

        return view(
            'staff.requests.show',
            [
                'request' => $request,
                'events' => RequestTimeline::events($request),
                'awaitingReceipt' => Fulfillment::isAwaitingReceipt($request)
                    && $request->receiverUser()?->id === Auth::id(),
                'fulfillmentComplete' => Fulfillment::isComplete($request),
                'ownHasSignature' => SignatureStore::userHasSignature(Auth::user()),
            ]
        );
    }

    /**
     * Penerima mengonfirmasi barang sudah diterima (tanda tangan digital pada BAST).
     */
    public function receive(Request $http, $id)
    {
        $request = Pengajuan::query()->mine(Auth::user())->findOrFail($id);

        $data = $http->validate(['note' => ['nullable', 'string', 'max:1000']]);

        try {
            Fulfillment::confirmReceipt($request, Auth::user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('receipt_error', $e->getMessage());
        }

        return redirect()
            ->route('staff.requests.show', $request->id)
            ->with('receipt_success', 'Terima kasih, penerimaan barang sudah dikonfirmasi.');
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
