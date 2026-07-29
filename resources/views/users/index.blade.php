<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold text-white">Manajemen User</h1>
            <p class="text-[10px] text-white/80">
                Kelola role, status, akses banyak entitas, dan akses banyak gudang.
            </p>
        </div>
    </x-slot>

    <div class="space-y-3">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-md border border-slate-200 bg-white p-3 shadow-sm">
            <form method="GET" action="{{ route('users.index') }}" class="space-y-3">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">Cari User</label>
                        <input name="search" value="{{ $search }}"
                               class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs"
                               placeholder="Nama atau email">
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">Role</label>
                        <select name="role" class="block w-full rounded-md border-slate-300 py-2 text-xs">
                            <option value="">Semua Role</option>
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption }}" @selected($role === $roleOption)>
                                    {{ match ($roleOption) {
                                        'super_admin' => 'Super Admin',
                                        'admin' => 'Admin',
                                        'approver' => 'Approver',
                                        default => 'Staff',
                                    } }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">Entitas Akses</label>
                        <select name="entity_id" class="block w-full rounded-md border-slate-300 py-2 text-xs">
                            <option value="">Semua Entitas</option>
                            @foreach ($entities as $entity)
                                <option value="{{ $entity->id }}" @selected((string) $entityId === (string) $entity->id)>
                                    {{ $entity->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">Gudang Akses</label>
                        <select name="warehouse_id" class="block w-full rounded-md border-slate-300 py-2 text-xs">
                            <option value="">Semua Gudang</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">Status</label>
                        <select name="status" class="block w-full rounded-md border-slate-300 py-2 text-xs">
                            <option value="">Semua Status</option>
                            <option value="active" @selected($status === 'active')>Aktif</option>
                            <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('users.index') }}"
                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                        Reset
                    </a>
                    <button class="rounded-md bg-sky-600 px-3 py-1.5 text-[11px] font-semibold text-white">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Daftar User</h2>
                    <p class="text-[10px] text-slate-500">{{ $users->total() }} data user</p>
                </div>

                <a href="{{ route('users.create') }}"
                   class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white">
                    Tambah User
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-[9px] font-bold uppercase text-slate-500">User</th>
                            <th class="px-3 py-2.5 text-center text-[9px] font-bold uppercase text-slate-500">Role</th>
                            <th class="px-3 py-2.5 text-left text-[9px] font-bold uppercase text-slate-500">Entitas Default</th>
                            <th class="px-3 py-2.5 text-left text-[9px] font-bold uppercase text-slate-500">Akses Entitas</th>
                            <th class="px-3 py-2.5 text-left text-[9px] font-bold uppercase text-slate-500">Gudang Default</th>
                            <th class="px-3 py-2.5 text-left text-[9px] font-bold uppercase text-slate-500">Akses Gudang</th>
                            <th class="px-3 py-2.5 text-center text-[9px] font-bold uppercase text-slate-500">Status</th>
                            <th class="px-3 py-2.5 text-center text-[9px] font-bold uppercase text-slate-500">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-3">
                                    <p class="text-[11px] font-bold text-slate-800">{{ $user->name }}</p>
                                    <p class="text-[9px] text-slate-500">{{ $user->email }}</p>
                                </td>

                                <td class="px-3 py-3 text-center">
                                    <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[9px] font-bold text-sky-700">
                                        {{ match ($user->role) {
                                            'super_admin' => 'Super Admin',
                                            'admin' => 'Admin',
                                            'approver' => 'Approver',
                                            default => 'Staff',
                                        } }}
                                    </span>
                                </td>

                                <td class="px-3 py-3 text-[10px] text-slate-700">
                                    {{ $user->isSuperAdmin() ? 'Semua' : ($user->entity?->code ?? '-') }}
                                </td>

                                <td class="px-3 py-3">
                                    @if ($user->isSuperAdmin())
                                        <span class="text-[10px] font-semibold text-violet-700">Semua Entitas</span>
                                    @else
                                        <div class="flex max-w-[220px] flex-wrap gap-1">
                                            @forelse ($user->entities as $entity)
                                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-semibold text-blue-700">
                                                    {{ $entity->code }}
                                                </span>
                                            @empty
                                                <span class="text-[9px] text-red-600">Belum diatur</span>
                                            @endforelse
                                        </div>
                                    @endif
                                </td>

                                <td class="px-3 py-3 text-[10px] text-slate-700">
                                    {{ $user->isSuperAdmin() ? 'Semua' : ($user->warehouse?->name ?? '-') }}
                                </td>

                                <td class="px-3 py-3">
                                    @if ($user->isSuperAdmin())
                                        <span class="text-[10px] font-semibold text-violet-700">Semua Gudang</span>
                                    @else
                                        <div class="flex max-w-[250px] flex-wrap gap-1">
                                            @forelse ($user->warehouses as $warehouse)
                                                <span class="rounded-full bg-violet-50 px-2 py-0.5 text-[9px] font-semibold text-violet-700">
                                                    {{ $warehouse->name }}
                                                </span>
                                            @empty
                                                <span class="text-[9px] text-red-600">Belum diatur</span>
                                            @endforelse
                                        </div>
                                    @endif
                                </td>

                                <td class="px-3 py-3 text-center">
                                    <span class="rounded-full px-2 py-0.5 text-[9px] font-bold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td class="px-3 py-3">
                                    <div class="flex justify-center gap-1">
                                        <a href="{{ route('users.edit', $user) }}"
                                           class="rounded-md border border-sky-200 bg-sky-50 px-2 py-1 text-[9px] font-semibold text-sky-700">
                                            Edit
                                        </a>

                                        @if ($user->id !== auth()->id())
                                            @if ($user->is_active)
                                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                                      onsubmit="return confirm('Nonaktifkan user ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-md border border-red-200 bg-red-50 px-2 py-1 text-[9px] font-semibold text-red-700">
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('users.activate', $user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[9px] font-semibold text-emerald-700">
                                                        Aktifkan
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-xs text-slate-500">
                                    Data user tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
