@php
    use Illuminate\Support\Str;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Master Barang ATK/RTK
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Kelola data barang, stok, kategori, lokasi, dan status barang.
            </p>
        </div>
    </x-slot>

    <div class="space-y-3">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-emerald-800 shadow-sm">
                <div class="flex gap-2">
                    <svg class="mt-0.5 h-4 w-4 flex-none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-5M12 3.5l7 3v5.5c0 4.5-2.8 7.6-7 8.5-4.2-.9-7-4-7-8.5V6.5l7-3Z" />
                    </svg>
                    <div>
                        <p class="text-xs font-semibold leading-4">Berhasil</p>
                        <p class="text-[11px] leading-4">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-red-800 shadow-sm">
                <div class="flex gap-2">
                    <svg class="mt-0.5 h-4 w-4 flex-none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    </svg>
                    <div>
                        <p class="text-xs font-semibold leading-4">Terjadi Kesalahan</p>
                        <p class="text-[11px] leading-4">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xs font-semibold leading-5 text-slate-800">
                            Data Barang
                        </h2>
                        <p class="text-[10px] leading-4 text-slate-500">
                            Daftar barang ATK/RTK yang terdaftar di sistem.
                        </p>
                    </div>

                    <a href="{{ route('items.create') }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                        Tambah Barang
                    </a>
                </div>
            </div>

            <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-3">
                <form method="GET" action="{{ route('items.index') }}">
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-5">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Cari kode / nama"
                            class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >

                        <select
                            name="category_id"
                            class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($categoryId == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        <select
                            name="entity_id"
                            class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Semua Entitas</option>
                            @foreach ($entities as $entity)
                                <option value="{{ $entity->id }}" @selected($entityId == $entity->id)>
                                    {{ $entity->code }} - {{ $entity->name }}
                                </option>
                            @endforeach
                        </select>

                        <select
                            name="status"
                            class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="1" @selected($status === '1')>Aktif</option>
                            <option value="0" @selected($status === '0')>Nonaktif</option>
                        </select>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18M6 12h12M10 19h4" />
                                </svg>
                                Filter
                            </button>

                            <a
                                href="{{ route('items.index') }}"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                            >
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Kode</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Nama Barang</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Kategori</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Satuan</th>

                            @if ($selectedEntity)
                                <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Stok {{ $selectedEntity->code }}
                                </th>
                            @else
                                <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Stok ANR
                                </th>
                                <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Stok MAA
                                </th>
                            @endif

                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Lokasi</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($items as $item)
                            @php
                                $stockAnr = $item->itemStocks->firstWhere('entity.code', 'ANR');
                                $stockMaa = $item->itemStocks->firstWhere('entity.code', 'MAA');

                                $selectedStock = $selectedEntity
                                    ? $item->itemStocks->firstWhere('entity_id', $selectedEntity->id)
                                    : null;
                            @endphp

                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $items->firstItem() + $loop->index }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $item->code }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <div class="flex items-start gap-2.5">
                                        <div class="flex h-8 w-8 flex-none items-center justify-center rounded-md bg-indigo-100 text-indigo-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 8.25-9-5.25-9 5.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21M3 8.25v7.5L12 21" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold leading-5 text-slate-800">
                                                {{ $item->name }}
                                            </p>

                                            @if ($item->description)
                                                <p class="max-w-xs truncate text-[10px] leading-4 text-slate-500">
                                                    {{ Str::limit($item->description, 55) }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $item->category->name ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $item->unit }}
                                </td>

                                @if ($selectedEntity)
                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        @if ($selectedStock)
                                            <div class="{{ $selectedStock->is_low_stock ? 'text-red-600' : 'text-slate-700' }}">
                                                <p class="text-xs font-semibold leading-4">
                                                    {{ $selectedStock->current_stock }}
                                                </p>
                                                <p class="text-[10px] leading-4 text-slate-500">
                                                    Min: {{ $selectedStock->minimum_stock }}
                                                </p>
                                            </div>

                                            @if ($selectedStock->is_low_stock)
                                                <span class="mt-1 inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                                    Menipis
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-[10px] text-slate-400">
                                                Belum ada
                                            </span>
                                        @endif
                                    </td>
                                @else
                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        @if ($stockAnr)
                                            <div class="{{ $stockAnr->is_low_stock ? 'text-red-600' : 'text-slate-700' }}">
                                                <p class="text-xs font-semibold leading-4">
                                                    {{ $stockAnr->current_stock }}
                                                </p>
                                                <p class="text-[10px] leading-4 text-slate-500">
                                                    Min: {{ $stockAnr->minimum_stock }}
                                                </p>
                                            </div>

                                            @if ($stockAnr->is_low_stock)
                                                <span class="mt-1 inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                                    Menipis
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-[10px] text-slate-400">
                                                Belum ada
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        @if ($stockMaa)
                                            <div class="{{ $stockMaa->is_low_stock ? 'text-red-600' : 'text-slate-700' }}">
                                                <p class="text-xs font-semibold leading-4">
                                                    {{ $stockMaa->current_stock }}
                                                </p>
                                                <p class="text-[10px] leading-4 text-slate-500">
                                                    Min: {{ $stockMaa->minimum_stock }}
                                                </p>
                                            </div>

                                            @if ($stockMaa->is_low_stock)
                                                <span class="mt-1 inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                                    Menipis
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-[10px] text-slate-400">
                                                Belum ada
                                            </span>
                                        @endif
                                    </td>
                                @endif

                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $item->storage_location ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    @if ($item->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('items.edit', $item) }}"
                                           class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200 hover:bg-amber-100">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.5 18.152 4 19.5l1.348-4.5L16.862 4.487Z" />
                                            </svg>
                                            Edit
                                        </a>

                                        <form action="{{ route('items.destroy', $item) }}"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus barang ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-1.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9M19 6h-4.5l-.9-1.5h-3.2L9.5 6H5m2 0v13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V6" />
                                                </svg>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $selectedEntity ? 9 : 10 }}" class="px-4 py-10 text-center">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 8.25-9-5.25-9 5.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21M3 8.25v7.5L12 21" />
                                        </svg>
                                    </div>

                                    <h3 class="mt-3 text-xs font-semibold text-slate-800">
                                        Belum ada data barang
                                    </h3>

                                    <p class="mt-1 text-[11px] text-slate-500">
                                        Silakan tambah barang terlebih dahulu.
                                    </p>

                                    <a href="{{ route('items.create') }}"
                                       class="mt-4 inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800">
                                        Tambah Barang
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="border-t border-slate-200 px-4 py-3 text-xs">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>