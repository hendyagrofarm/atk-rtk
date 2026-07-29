@php
    use Illuminate\Support\Str;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-white">
                Master Barang dan Stok
            </h1>

            <p class="text-[10px] leading-4 text-white/80">
                Kelola barang dan pantau stok per entitas serta gudang.
            </p>
        </div>
    </x-slot>

    <div class="space-y-3">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-[11px] text-emerald-800 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-[11px] text-red-800 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xs font-semibold text-slate-800">
                        Data Barang
                    </h2>

                    <p class="text-[10px] text-slate-500">
                        Stok ditampilkan berdasarkan kombinasi entitas, gudang, dan barang.
                    </p>
                </div>

                <a href="{{ route('items.create') }}"
                   class="inline-flex items-center justify-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                    Tambah Barang
                </a>
            </div>

            <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-3">
                <form method="GET" action="{{ route('items.index') }}" class="space-y-2">
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-6">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Cari kode / nama"
                            class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm"
                        >

                        <select name="category_id"
                                class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            <option value="">Semua Kategori</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                        @selected((string) $categoryId === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="entity_id"
                                class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            <option value="">Semua Entitas Akses</option>

                            @foreach ($entities as $entity)
                                <option value="{{ $entity->id }}"
                                        @selected((string) $entityId === (string) $entity->id)>
                                    {{ $entity->code }} - {{ $entity->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="warehouse_id"
                                class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            <option value="">Semua Gudang Akses</option>

                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                        @selected((string) $warehouseId === (string) $warehouse->id)>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="stock_status"
                                class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            <option value="">Semua Kondisi Stok</option>
                            <option value="low" @selected($stockStatus === 'low')>
                                Stok Menipis
                            </option>
                            <option value="safe" @selected($stockStatus === 'safe')>
                                Stok Aman
                            </option>
                        </select>

                        <select name="status"
                                class="rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            <option value="">Semua Status Barang</option>
                            <option value="1" @selected($status === '1')>Aktif</option>
                            <option value="0" @selected($status === '0')>Nonaktif</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('items.index') }}"
                           class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                            Reset
                        </a>

                        <button type="submit"
                                class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">
                                No
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">
                                Barang
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">
                                Kategori
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">
                                Satuan
                            </th>
                            <th class="min-w-[410px] px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">
                                Stok Per Lokasi
                            </th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">
                                Total
                            </th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">
                                Status
                            </th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($items as $item)
                            @php
                                $visibleTotal = $item->itemStocks->sum('current_stock');
                            @endphp

                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-4 py-3 text-[11px] text-slate-600">
                                    {{ $items->firstItem() + $loop->index }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-indigo-100 text-indigo-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 8.25-9-5.25-9 5.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21M3 8.25v7.5L12 21" />
                                            </svg>
                                        </div>

                                        <div>
                                            <p class="text-xs font-semibold text-slate-800">
                                                {{ $item->name }}
                                            </p>

                                            <p class="text-[10px] text-slate-500">
                                                {{ $item->code }} · {{ $item->type }}
                                            </p>

                                            @if ($item->description)
                                                <p class="mt-0.5 max-w-[240px] text-[9px] text-slate-400">
                                                    {{ Str::limit($item->description, 60) }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-[11px] text-slate-600">
                                    {{ $item->category?->name ?? '-' }}
                                </td>

                                <td class="px-4 py-3 text-[11px] text-slate-600">
                                    {{ $item->unit }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @forelse ($item->itemStocks as $stock)
                                            <div class="rounded-md border px-3 py-2 {{ $stock->is_low_stock ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-white' }}">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div>
                                                        <p class="text-[10px] font-bold text-slate-800">
                                                            {{ $stock->entity?->code ?? '-' }}
                                                            ·
                                                            {{ $stock->warehouse?->name ?? '-' }}
                                                        </p>

                                                        <p class="text-[9px] text-slate-500">
                                                            {{ $stock->warehouse?->code ?? '-' }}
                                                        </p>
                                                    </div>

                                                    <div class="text-right">
                                                        <p class="text-sm font-bold {{ $stock->is_low_stock ? 'text-red-700' : 'text-slate-800' }}">
                                                            {{ $stock->current_stock }}
                                                        </p>

                                                        <p class="text-[9px] text-slate-500">
                                                            Min {{ $stock->minimum_stock }}
                                                        </p>
                                                    </div>
                                                </div>

                                                @if ($stock->is_low_stock)
                                                    <span class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[8px] font-bold text-red-700">
                                                        Menipis
                                                    </span>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="rounded-md border border-dashed border-slate-300 px-3 py-3 text-[10px] text-slate-400 sm:col-span-2">
                                                Belum ada stok pada lokasi yang dipilih.
                                            </div>
                                        @endforelse
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <p class="text-sm font-bold text-slate-800">
                                        {{ $visibleTotal }}
                                    </p>

                                    <p class="text-[9px] text-slate-500">
                                        lokasi tampil
                                    </p>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold {{ $item->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-1.5">
                                        <a href="{{ route('items.edit', $item) }}"
                                           class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-[9px] font-semibold text-amber-700">
                                            Edit
                                        </a>

                                        <form action="{{ route('items.destroy', $item) }}"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus barang ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="rounded-md border border-red-200 bg-red-50 px-2 py-1 text-[9px] font-semibold text-red-700">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-[11px] text-slate-500">
                                    Data barang tidak ditemukan.
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
