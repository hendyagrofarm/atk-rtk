<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    /**
     * Menampilkan daftar seluruh gudang.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $status = $request->get('status');

        $warehouses = Warehouse::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('location', 'like', '%' . $search . '%');
                });
            })
            ->when(
                in_array($status, ['active', 'inactive'], true),
                function ($query) use ($status) {
                    $query->where(
                        'is_active',
                        $status === 'active'
                    );
                }
            )
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view(
            'warehouses.index',
            compact(
                'warehouses',
                'search',
                'status'
            )
        );
    }

    /**
     * Menampilkan halaman tambah gudang.
     */
    public function create()
    {
        return view('warehouses.create');
    }

    /**
     * Menyimpan gudang baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    'unique:warehouses,code',
                ],

                'name' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'location' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'description' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'code.required' => 'Kode gudang wajib diisi.',
                'code.string' => 'Kode gudang harus berupa teks.',
                'code.max' => 'Kode gudang maksimal 50 karakter.',
                'code.unique' => 'Kode gudang sudah digunakan.',

                'name.required' => 'Nama gudang wajib diisi.',
                'name.string' => 'Nama gudang harus berupa teks.',
                'name.max' => 'Nama gudang maksimal 150 karakter.',

                'location.string' => 'Lokasi gudang harus berupa teks.',
                'location.max' => 'Lokasi gudang maksimal 255 karakter.',

                'description.string' => 'Keterangan harus berupa teks.',
                'description.max' => 'Keterangan maksimal 1.000 karakter.',

                'is_active.boolean' => 'Status gudang tidak valid.',
            ]
        );

        $validated['code'] = strtoupper(
            trim($validated['code'])
        );

        $validated['name'] = trim(
            $validated['name']
        );

        $validated['location'] = filled(
            $validated['location'] ?? null
        )
            ? trim($validated['location'])
            : null;

        $validated['description'] = filled(
            $validated['description'] ?? null
        )
            ? trim($validated['description'])
            : null;

        $validated['is_active'] = $request->boolean(
            'is_active'
        );

        Warehouse::create($validated);

        return redirect()
            ->route('warehouses.index')
            ->with(
                'success',
                'Gudang berhasil ditambahkan.'
            );
    }

    /**
     * Method show tidak digunakan.
     */
    public function show(Warehouse $warehouse)
    {
        return redirect()
            ->route('warehouses.index');
    }

    /**
     * Menampilkan halaman edit gudang.
     */
    public function edit(Warehouse $warehouse)
    {
        return view(
            'warehouses.edit',
            compact('warehouse')
        );
    }

    /**
     * Memperbarui data gudang.
     */
    public function update(
        Request $request,
        Warehouse $warehouse
    ) {
        $validated = $request->validate(
            [
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique(
                        'warehouses',
                        'code'
                    )->ignore($warehouse->id),
                ],

                'name' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'location' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'description' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'code.required' => 'Kode gudang wajib diisi.',
                'code.string' => 'Kode gudang harus berupa teks.',
                'code.max' => 'Kode gudang maksimal 50 karakter.',
                'code.unique' => 'Kode gudang sudah digunakan.',

                'name.required' => 'Nama gudang wajib diisi.',
                'name.string' => 'Nama gudang harus berupa teks.',
                'name.max' => 'Nama gudang maksimal 150 karakter.',

                'location.string' => 'Lokasi gudang harus berupa teks.',
                'location.max' => 'Lokasi gudang maksimal 255 karakter.',

                'description.string' => 'Keterangan harus berupa teks.',
                'description.max' => 'Keterangan maksimal 1.000 karakter.',

                'is_active.boolean' => 'Status gudang tidak valid.',
            ]
        );

        $validated['code'] = strtoupper(
            trim($validated['code'])
        );

        $validated['name'] = trim(
            $validated['name']
        );

        $validated['location'] = filled(
            $validated['location'] ?? null
        )
            ? trim($validated['location'])
            : null;

        $validated['description'] = filled(
            $validated['description'] ?? null
        )
            ? trim($validated['description'])
            : null;

        $validated['is_active'] = $request->boolean(
            'is_active'
        );

        $warehouse->update($validated);

        return redirect()
            ->route('warehouses.index')
            ->with(
                'success',
                'Data gudang berhasil diperbarui.'
            );
    }

    /**
     * Mengaktifkan atau menonaktifkan gudang.
     */
    public function toggleStatus(Warehouse $warehouse)
    {
        $warehouse->update([
            'is_active' => ! $warehouse->is_active,
        ]);

        $message = $warehouse->is_active
            ? 'Gudang berhasil diaktifkan.'
            : 'Gudang berhasil dinonaktifkan.';

        return redirect()
            ->route('warehouses.index')
            ->with('success', $message);
    }

    /**
     * Menghapus gudang.
     */
    public function destroy(Warehouse $warehouse)
    {
        /*
        |--------------------------------------------------------------------------
        | Pengecekan relasi akan ditambahkan pada tahap berikutnya
        |--------------------------------------------------------------------------
        | Saat ini tabel gudang belum terhubung ke user, stok, dan transaksi.
        | Setelah relasi dibuat, gudang yang sudah digunakan tidak boleh dihapus.
        */

        $warehouse->delete();

        return redirect()
            ->route('warehouses.index')
            ->with(
                'success',
                'Gudang berhasil dihapus.'
            );
    }
}