<x-app-layout>
    <x-slot name="header">
    <div>
        <h1 class="text-sm font-semibold leading-5 text-white">
            Master Gudang
        </h1>

        <p class="text-[10px] leading-4 text-white/80">
            Kelola lokasi penyimpanan barang perusahaan.
        </p>
    </div>
</x-slot>

    <div class="space-y-3">
        {{-- Pesan sukses --}}
        @if (session('success'))
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition
                class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 shadow-sm"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-2">
                        <svg
                            class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m5 12.5 4 4L19 6.5"
                            />
                        </svg>

                        <div>
                            <p class="text-[11px] font-semibold text-emerald-800">
                                Berhasil
                            </p>

                            <p class="text-[10px] text-emerald-700">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="show = false"
                        class="rounded p-0.5 text-emerald-600 hover:bg-emerald-100"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Statistik --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-md border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-sky-100 text-sky-700">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m21 8.25-9-5.25-9 5.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21M3 8.25v7.5L12 21"
                            />
                        </svg>
                    </div>

                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">
                            Total Gudang
                        </p>

                        <p class="text-lg font-bold leading-6 text-slate-800">
                            {{ \App\Models\Warehouse::count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-md border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m5 12.5 4 4L19 6.5"
                            />
                        </svg>
                    </div>

                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">
                            Gudang Aktif
                        </p>

                        <p class="text-lg font-bold leading-6 text-slate-800">
                            {{ \App\Models\Warehouse::where('is_active', true)->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-md border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-slate-200 text-slate-600">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 6l12 12M18 6 6 18"
                            />
                        </svg>
                    </div>

                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">
                            Gudang Nonaktif
                        </p>

                        <p class="text-lg font-bold leading-6 text-slate-800">
                            {{ \App\Models\Warehouse::where('is_active', false)->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="rounded-md border border-slate-200 bg-white p-3 shadow-sm">
            <form
                action="{{ route('warehouses.index') }}"
                method="GET"
                class="grid grid-cols-1 gap-3 md:grid-cols-12"
            >
                <div class="md:col-span-6">
                    <label
                        for="search"
                        class="mb-1 block text-[10px] font-semibold text-slate-700"
                    >
                        Cari Gudang
                    </label>

                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="11" cy="11" r="7"></circle>
                            <path
                                stroke-linecap="round"
                                d="m20 20-3.5-3.5"
                            ></path>
                        </svg>

                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ $search }}"
                            class="block w-full rounded-md border-slate-300 py-2 pl-9 pr-3 text-xs shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            placeholder="Cari kode, nama, atau lokasi gudang"
                        >
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label
                        for="status"
                        class="mb-1 block text-[10px] font-semibold text-slate-700"
                    >
                        Status
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="block w-full rounded-md border-slate-300 py-2 text-xs shadow-sm focus:border-sky-500 focus:ring-sky-500"
                    >
                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="active"
                            @selected($status === 'active')
                        >
                            Aktif
                        </option>

                        <option
                            value="inactive"
                            @selected($status === 'inactive')
                        >
                            Nonaktif
                        </option>
                    </select>
                </div>

                <div class="flex items-end gap-2 md:col-span-3">
                    <button
                        type="submit"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md bg-sky-600 px-3 py-2 text-[11px] font-semibold text-white shadow-sm transition hover:bg-sky-700"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="11" cy="11" r="7"></circle>
                            <path
                                stroke-linecap="round"
                                d="m20 20-3.5-3.5"
                            ></path>
                        </svg>

                        Cari
                    </button>

                    <a
                        href="{{ route('warehouses.index') }}"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabel --}}
        <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-2 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">
                        Daftar Gudang
                    </h2>

                    <p class="text-[10px] text-slate-500">
                        Menampilkan {{ $warehouses->firstItem() ?? 0 }}
                        sampai {{ $warehouses->lastItem() ?? 0 }}
                        dari {{ $warehouses->total() }} data.
                    </p>
                </div>

                <a
                    href="{{ route('warehouses.create') }}"
                    class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                >
                    <svg
                        class="h-3.5 w-3.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 5v14m7-7H5"
                        />
                    </svg>

                    Tambah Gudang
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-12 px-4 py-2.5 text-center text-[9px] font-bold uppercase tracking-wide text-slate-500">
                                No.
                            </th>

                            <th class="px-4 py-2.5 text-left text-[9px] font-bold uppercase tracking-wide text-slate-500">
                                Kode
                            </th>

                            <th class="px-4 py-2.5 text-left text-[9px] font-bold uppercase tracking-wide text-slate-500">
                                Nama Gudang
                            </th>

                            <th class="px-4 py-2.5 text-left text-[9px] font-bold uppercase tracking-wide text-slate-500">
                                Lokasi
                            </th>

                            <th class="px-4 py-2.5 text-center text-[9px] font-bold uppercase tracking-wide text-slate-500">
                                Status
                            </th>

                            <th class="w-40 px-4 py-2.5 text-center text-[9px] font-bold uppercase tracking-wide text-slate-500">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($warehouses as $warehouse)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-center text-[11px] text-slate-500">
                                    {{ $warehouses->firstItem() + $loop->index }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-md bg-sky-50 px-2 py-1 font-mono text-[10px] font-bold text-sky-700 ring-1 ring-inset ring-sky-200">
                                        {{ $warehouse->code }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <p class="text-[11px] font-bold text-slate-800">
                                        {{ $warehouse->name }}
                                    </p>

                                    @if ($warehouse->description)
                                        <p class="mt-0.5 max-w-md truncate text-[10px] text-slate-500">
                                            {{ $warehouse->description }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-[11px] text-slate-600">
                                    {{ $warehouse->location ?: '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @if ($warehouse->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- Edit --}}
                                        <a
                                            href="{{ route('warehouses.edit', $warehouse) }}"
                                            title="Edit Gudang"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L18.55 2.799"
                                                />
                                            </svg>
                                        </a>

                                        {{-- Toggle status --}}
                                        <form
                                            action="{{ route('warehouses.toggle-status', $warehouse) }}"
                                            method="POST"
                                            onsubmit="return confirm('{{ $warehouse->is_active
                                                ? 'Nonaktifkan gudang ini?'
                                                : 'Aktifkan kembali gudang ini?' }}')"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                title="{{ $warehouse->is_active
                                                    ? 'Nonaktifkan Gudang'
                                                    : 'Aktifkan Gudang' }}"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border transition
                                                    {{ $warehouse->is_active
                                                        ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
                                                        : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                            >
                                                @if ($warehouse->is_active)
                                                    <svg
                                                        class="h-3.5 w-3.5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.8"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <circle cx="12" cy="12" r="8"></circle>
                                                        <path
                                                            stroke-linecap="round"
                                                            d="M8.5 8.5 15.5 15.5"
                                                        ></path>
                                                    </svg>
                                                @else
                                                    <svg
                                                        class="h-3.5 w-3.5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.8"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="m5 12.5 4 4L19 6.5"
                                                        />
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>

                                        {{-- Hapus --}}
                                        <form
                                            action="{{ route('warehouses.destroy', $warehouse) }}"
                                            method="POST"
                                            onsubmit="return confirm('Hapus gudang {{ addslashes($warehouse->name) }}? Data yang dihapus tidak dapat dikembalikan.')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                title="Hapus Gudang"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-700 transition hover:bg-red-100"
                                            >
                                                <svg
                                                    class="h-3.5 w-3.5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M4 7h16M9 7V4.5h6V7m-8 0 .7 12h8.6L17 7M10 11v5m4-5v5"
                                                    />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-4 py-12 text-center"
                                >
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg
                                            class="h-6 w-6"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m21 8.25-9-5.25-9 5.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21M3 8.25v7.5L12 21"
                                            />
                                        </svg>
                                    </div>

                                    <p class="mt-3 text-xs font-semibold text-slate-700">
                                        Data gudang tidak ditemukan
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-500">
                                        Tambahkan gudang baru atau ubah filter pencarian.
                                    </p>

                                    <a
                                        href="{{ route('warehouses.create') }}"
                                        class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-700"
                                    >
                                        Tambah Gudang
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($warehouses->hasPages())
                <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                    {{ $warehouses->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>