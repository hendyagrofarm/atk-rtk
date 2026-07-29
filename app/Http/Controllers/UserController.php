<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    private array $roles = [
        'super_admin',
        'admin',
        'staff',
        'approver',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $role = $request->input('role');
        $status = $request->input('status');
        $entityId = $request->input('entity_id');
        $warehouseId = $request->input('warehouse_id');

        $users = User::query()
            ->with([
                'entity',
                'warehouse',
                'entities',
                'warehouses',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when(
                in_array($role, $this->roles, true),
                fn ($query) => $query->where('role', $role)
            )
            ->when(
                in_array($status, ['active', 'inactive'], true),
                fn ($query) => $query->where(
                    'is_active',
                    $status === 'active'
                )
            )
            ->when(filled($entityId), function ($query) use ($entityId) {
                $query->where(function ($subQuery) use ($entityId) {
                    $subQuery
                        ->where('role', 'super_admin')
                        ->orWhereHas(
                            'entities',
                            fn ($entityQuery) => $entityQuery->where(
                                'entities.id',
                                $entityId
                            )
                        )
                        ->orWhere('entity_id', $entityId);
                });
            })
            ->when(filled($warehouseId), function ($query) use ($warehouseId) {
                $query->where(function ($subQuery) use ($warehouseId) {
                    $subQuery
                        ->where('role', 'super_admin')
                        ->orWhereHas(
                            'warehouses',
                            fn ($warehouseQuery) => $warehouseQuery->where(
                                'warehouses.id',
                                $warehouseId
                            )
                        )
                        ->orWhere('warehouse_id', $warehouseId);
                });
            })
            ->orderByRaw("
                CASE
                    WHEN role = 'super_admin' THEN 1
                    WHEN role = 'admin' THEN 2
                    WHEN role = 'approver' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $entities = Entity::query()
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->get();

        return view('users.index', compact(
            'users',
            'entities',
            'warehouses',
            'search',
            'role',
            'status',
            'entityId',
            'warehouseId'
        ))->with('roles', $this->roles);
    }

    public function create()
    {
        return view('users.create', [
            'roles' => $this->roles,
            'entities' => Entity::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request);

        try {
            DB::transaction(function () use ($request, $validated) {
                $isSuperAdmin = $validated['role'] === 'super_admin';

                $entityIds = $isSuperAdmin
                    ? []
                    : collect($validated['entity_ids'] ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();

                $warehouseIds = $isSuperAdmin
                    ? []
                    : collect($validated['warehouse_ids'] ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();

                $defaultEntityId = $isSuperAdmin
                    ? null
                    : (int) $validated['entity_id'];

                $defaultWarehouseId = $isSuperAdmin
                    ? null
                    : (int) $validated['warehouse_id'];

                $this->ensureDefaultsAreAllowed(
                    $defaultEntityId,
                    $defaultWarehouseId,
                    $entityIds,
                    $warehouseIds
                );

                $user = User::create([
                    'name' => trim($validated['name']),
                    'email' => strtolower(trim($validated['email'])),
                    'role' => $validated['role'],
                    'entity_id' => $defaultEntityId,
                    'warehouse_id' => $defaultWarehouseId,
                    'password' => $validated['password'],
                    'is_active' => $request->boolean('is_active'),
                ]);

                $user->entities()->sync($entityIds);
                $user->warehouses()->sync($warehouseIds);
            });

            return redirect()
                ->route('users.index')
                ->with('success', 'User baru dan hak akses lokasinya berhasil dibuat.');
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', $e->getMessage() ?: 'Gagal membuat user.');
        }
    }

    public function edit(User $user)
    {
        $user->load([
            'entity',
            'warehouse',
            'entities',
            'warehouses',
        ]);

        $entityIds = $user->entities
            ->pluck('id')
            ->push($user->entity_id)
            ->filter()
            ->unique()
            ->values();

        $warehouseIds = $user->warehouses
            ->pluck('id')
            ->push($user->warehouse_id)
            ->filter()
            ->unique()
            ->values();

        $entities = Entity::query()
            ->where(function ($query) use ($entityIds) {
                $query
                    ->where('is_active', true)
                    ->orWhereIn('id', $entityIds);
            })
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::query()
            ->where(function ($query) use ($warehouseIds) {
                $query
                    ->where('is_active', true)
                    ->orWhereIn('id', $warehouseIds);
            })
            ->orderBy('name')
            ->get();

        return view('users.edit', [
            'user' => $user,
            'roles' => $this->roles,
            'entities' => $entities,
            'warehouses' => $warehouses,
            'selectedEntityIds' => $entityIds->map(fn ($id) => (string) $id)->all(),
            'selectedWarehouseIds' => $warehouseIds->map(fn ($id) => (string) $id)->all(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validateUser($request, $user);

        if (
            $user->id === Auth::id()
            && $validated['role'] !== $user->role
        ) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak boleh mengubah role akun sendiri.');
        }

        if (
            $user->id === Auth::id()
            && ! $request->boolean('is_active')
        ) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak boleh menonaktifkan akun sendiri.');
        }

        try {
            DB::transaction(function () use ($request, $validated, $user) {
                $isSuperAdmin = $validated['role'] === 'super_admin';

                $entityIds = $isSuperAdmin
                    ? []
                    : collect($validated['entity_ids'] ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();

                $warehouseIds = $isSuperAdmin
                    ? []
                    : collect($validated['warehouse_ids'] ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();

                $defaultEntityId = $isSuperAdmin
                    ? null
                    : (int) $validated['entity_id'];

                $defaultWarehouseId = $isSuperAdmin
                    ? null
                    : (int) $validated['warehouse_id'];

                $this->ensureDefaultsAreAllowed(
                    $defaultEntityId,
                    $defaultWarehouseId,
                    $entityIds,
                    $warehouseIds
                );

                $data = [
                    'name' => trim($validated['name']),
                    'email' => strtolower(trim($validated['email'])),
                    'role' => $validated['role'],
                    'entity_id' => $defaultEntityId,
                    'warehouse_id' => $defaultWarehouseId,
                    'is_active' => $request->boolean('is_active'),
                ];

                if (! empty($validated['password'])) {
                    $data['password'] = $validated['password'];
                }

                $user->update($data);
                $user->entities()->sync($entityIds);
                $user->warehouses()->sync($warehouseIds);
            });

            return redirect()
                ->route('users.index')
                ->with('success', 'Data user dan hak akses lokasi berhasil diperbarui.');
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', $e->getMessage() ?: 'Gagal memperbarui user.');
        }
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak boleh menonaktifkan akun sendiri.');
        }

        if (
            $user->role === 'super_admin'
            && User::query()
                ->where('role', 'super_admin')
                ->where('is_active', true)
                ->count() <= 1
        ) {
            return back()->with(
                'error',
                'Super Admin aktif terakhir tidak boleh dinonaktifkan.'
            );
        }

        $user->update(['is_active' => false]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil dinonaktifkan.');
    }

    public function activate(User $user)
    {
        if ($user->is_active) {
            return redirect()
                ->route('users.index')
                ->with('success', 'User tersebut sudah aktif.');
        }

        if ($user->role !== 'super_admin') {
            $user->load(['entity', 'warehouse', 'entities', 'warehouses']);

            if (! $user->entity_id || ! $user->warehouse_id) {
                return redirect()
                    ->route('users.edit', $user)
                    ->with(
                        'error',
                        'Pilih entitas default dan gudang default terlebih dahulu.'
                    );
            }

            if (
                ! $user->canAccessEntity($user->entity_id)
                || ! $user->canAccessWarehouse($user->warehouse_id)
            ) {
                return redirect()
                    ->route('users.edit', $user)
                    ->with(
                        'error',
                        'Lokasi default harus termasuk dalam daftar hak akses user.'
                    );
            }

            if (! $user->entity?->is_active || ! $user->warehouse?->is_active) {
                return redirect()
                    ->route('users.edit', $user)
                    ->with(
                        'error',
                        'Entitas atau gudang default user sedang nonaktif.'
                    );
            }
        }

        $user->update(['is_active' => true]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diaktifkan kembali.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $isSuperAdmin = $request->input('role') === 'super_admin';

        return $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user?->id),
                ],
                'role' => ['required', Rule::in($this->roles)],

                'entity_ids' => [
                    Rule::requiredIf(! $isSuperAdmin),
                    'nullable',
                    'array',
                    'min:1',
                ],
                'entity_ids.*' => [
                    'integer',
                    Rule::exists('entities', 'id')->where('is_active', true),
                ],
                'entity_id' => [
                    Rule::requiredIf(! $isSuperAdmin),
                    'nullable',
                    'integer',
                    'exists:entities,id',
                ],

                'warehouse_ids' => [
                    Rule::requiredIf(! $isSuperAdmin),
                    'nullable',
                    'array',
                    'min:1',
                ],
                'warehouse_ids.*' => [
                    'integer',
                    Rule::exists('warehouses', 'id')->where('is_active', true),
                ],
                'warehouse_id' => [
                    Rule::requiredIf(! $isSuperAdmin),
                    'nullable',
                    'integer',
                    'exists:warehouses,id',
                ],

                'password' => [
                    $user ? 'nullable' : 'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
                'is_active' => ['nullable', 'boolean'],
            ],
            [
                'name.required' => 'Nama user wajib diisi.',
                'email.required' => 'Email user wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah digunakan user lain.',
                'role.required' => 'Role wajib dipilih.',
                'role.in' => 'Role yang dipilih tidak valid.',

                'entity_ids.required' => 'Pilih minimal satu entitas akses.',
                'entity_ids.array' => 'Data akses entitas tidak valid.',
                'entity_ids.min' => 'Pilih minimal satu entitas akses.',
                'entity_ids.*.exists' => 'Salah satu entitas tidak ditemukan atau nonaktif.',
                'entity_id.required' => 'Entitas default wajib dipilih.',
                'entity_id.exists' => 'Entitas default tidak ditemukan.',

                'warehouse_ids.required' => 'Pilih minimal satu gudang akses.',
                'warehouse_ids.array' => 'Data akses gudang tidak valid.',
                'warehouse_ids.min' => 'Pilih minimal satu gudang akses.',
                'warehouse_ids.*.exists' => 'Salah satu gudang tidak ditemukan atau nonaktif.',
                'warehouse_id.required' => 'Gudang default wajib dipilih.',
                'warehouse_id.exists' => 'Gudang default tidak ditemukan.',

                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal 8 karakter.',
                'password.confirmed' => 'Konfirmasi password tidak sama.',
            ]
        );
    }

    private function ensureDefaultsAreAllowed(
        ?int $defaultEntityId,
        ?int $defaultWarehouseId,
        array $entityIds,
        array $warehouseIds
    ): void {
        if (
            $defaultEntityId
            && ! in_array($defaultEntityId, $entityIds, true)
        ) {
            throw new \RuntimeException(
                'Entitas default harus dicentang pada daftar akses entitas.'
            );
        }

        if (
            $defaultWarehouseId
            && ! in_array($defaultWarehouseId, $warehouseIds, true)
        ) {
            throw new \RuntimeException(
                'Gudang default harus dicentang pada daftar akses gudang.'
            );
        }
    }
}
