<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold text-white">Master Entitas</h1>
                <p class="text-[10px] text-white/80">
                    Kelola entitas yang digunakan pada user, stok, transaksi, dan pengajuan.
                </p>
            </div>

            <a href="{{ route('entities.create') }}"
               class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-700">
                Tambah Entitas
            </a>
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

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('entities.index') }}">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari kode atau nama entitas"
                        class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >

                    <select name="status"
                            class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                            Filter
                        </button>

                        <a href="{{ route('entities.index') }}"
                           class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-center text-[11px] font-semibold text-slate-700 hover:bg-slate-100">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Kode</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Nama Entitas</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">User</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Stok</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Pengajuan</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Status</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($entities as $entity)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $entities->firstItem() + $loop->index }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700">
                                        {{ $entity->code }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-xs font-semibold text-slate-800">
                                    {{ $entity->name }}
                                </td>

                                <td class="px-4 py-2.5 text-center text-[11px] text-slate-700">
                                    {{ $entity->users_count + $entity->access_users_count }}
                                </td>

                                <td class="px-4 py-2.5 text-center text-[11px] text-slate-700">
                                    {{ $entity->item_stocks_count }}
                                </td>

                                <td class="px-4 py-2.5 text-center text-[11px] text-slate-700">
                                    {{ $entity->requests_count }}
                                </td>

                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $entity->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $entity->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <div class="flex justify-center gap-1.5">
                                        <a href="{{ route('entities.edit', $entity) }}"
                                           class="rounded-md bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-700 hover:bg-amber-100">
                                            Edit
                                        </a>

                                        <form method="POST"
                                              action="{{ route('entities.toggle-status', $entity) }}">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="rounded-md bg-sky-50 px-2 py-1 text-[10px] font-semibold text-sky-700 hover:bg-sky-100">
                                                {{ $entity->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('entities.destroy', $entity) }}"
                                              onsubmit="return confirm('Yakin ingin menghapus entitas ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="rounded-md bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700 hover:bg-red-100">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Data entitas belum tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($entities->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $entities->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
