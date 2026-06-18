<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold leading-5 text-slate-800">
                    Manajemen User
                </h2>
                <p class="text-[10px] leading-4 text-slate-500">
                    Kelola akun admin, staff, dan approver.
                </p>
            </div>

            <a href="{{ route('users.create') }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-indigo-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                </svg>
                Tambah User
            </a>
        </div>
    </x-slot>

    <div class="space-y-3">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Filter -->
        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <form method="GET" action="{{ route('users.index') }}" class="grid gap-2 md:grid-cols-4">
                <div>
                    <label for="search" class="mb-1 block text-[10px] font-semibold text-slate-600">
                        Cari User
                    </label>
                    <input
                        id="search"
                        name="search"
                        type="text"
                        value="{{ $search }}"
                        placeholder="Nama atau email"
                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label for="role" class="mb-1 block text-[10px] font-semibold text-slate-600">
                        Role
                    </label>
                    <select
                        id="role"
                        name="role"
                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Semua Role</option>
                        @foreach ($roles as $itemRole)
                            <option value="{{ $itemRole }}" @selected($role === $itemRole)>
                                {{ ucfirst($itemRole) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="mb-1 block text-[10px] font-semibold text-slate-600">
                        Status
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Semua Status</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="submit"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                        </svg>
                        Filter
                    </button>

                    <a href="{{ route('users.index') }}"
                       class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 shadow-sm hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                User
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Role
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Status
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Dibuat
                            </th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-[11px] font-bold text-indigo-700">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>

                                        <div>
                                            <div class="text-xs font-semibold leading-5 text-slate-800">
                                                {{ $user->name }}

                                                @if ($user->id === auth()->id())
                                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-semibold text-slate-500">
                                                        Anda
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="text-[10px] leading-4 text-slate-500">
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-2.5">
                                    @if ($user->role === 'admin')
                                        <span class="inline-flex rounded-full bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-700 ring-1 ring-inset ring-purple-200">
                                            Admin
                                        </span>
                                    @elseif ($user->role === 'approver')
                                        <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                            Approver
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            Staff
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5">
                                    @if ($user->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-500">
                                    {{ $user->created_at?->format('d M Y H:i') }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('users.edit', $user) }}"
                                           class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L8.25 18.402 4.5 19.5l1.098-3.75L16.862 4.487Z" />
                                            </svg>
                                            Edit
                                        </a>

                                        @if ($user->is_active)
                                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                                  onsubmit="return confirm('Yakin ingin menonaktifkan user ini?')">
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    @disabled($user->id === auth()->id())
                                                    class="inline-flex items-center gap-1 rounded-md border border-red-200 bg-red-50 px-2 py-1.5 text-[10px] font-semibold text-red-600 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                    Nonaktifkan
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('users.activate', $user) }}">
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1.5 text-[10px] font-semibold text-emerald-600 hover:bg-emerald-100"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-5M12 3.5l7 3v5.5c0 4.5-2.8 7.6-7 8.5-4.2-.9-7-4-7-8.5V6.5l7-3Z" />
                                                    </svg>
                                                    Aktifkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center">
                                    <div class="text-xs font-semibold text-slate-600">
                                        Belum ada data user.
                                    </div>
                                    <div class="mt-1 text-[10px] text-slate-400">
                                        Tambahkan user baru untuk admin, staff, atau approver.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-slate-200 px-4 py-3 text-xs">
                    {{ $users->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>