<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-white">
                Laporan Stok Barang
            </h1>

            <p class="text-[10px] leading-4 text-white/80">
                Posisi stok berdasarkan entitas, gudang, dan barang.
            </p>
        </div>
    </x-slot>

    <div class="space-y-3">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="flex flex-wrap gap-1.5">
                <a href="{{ route('reports.dashboard') }}"
                   class="inline-flex items-center rounded-md px-3 py-1.5 text-[11px] font-semibold transition {{ request()->routeIs('reports.dashboard') ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Dashboard
                </a>

                <a href="{{ route('reports.stock') }}"
                   class="inline-flex items-center rounded-md px-3 py-1.5 text-[11px] font-semibold transition {{ request()->routeIs('reports.stock') ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Stok
                </a>

                <a href="{{ route('reports.stock-in') }}"
                   class="inline-flex items-center rounded-md px-3 py-1.5 text-[11px] font-semibold transition {{ request()->routeIs('reports.stock-in') ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Stok Masuk
                </a>

                <a href="{{ route('reports.requests') }}"
                   class="inline-flex items-center rounded-md px-3 py-1.5 text-[11px] font-semibold transition {{ request()->routeIs('reports.requests') ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Pengajuan
                </a>

                <a href="{{ route('reports.stock-out') }}"
                   class="inline-flex items-center rounded-md px-3 py-1.5 text-[11px] font-semibold transition {{ request()->routeIs('reports.stock-out') ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Barang Keluar
                </a>

                <a href="{{ route('reports.stock-transfers') }}"
                   class="inline-flex items-center rounded-md px-3 py-1.5 text-[11px] font-semibold transition {{ request()->routeIs('reports.stock-transfers') ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Mutasi Stok
                </a>
                <a href="{{ route('reports.kpi') }}"
                   class="inline-flex items-center rounded-md px-3 py-1.5 text-[11px] font-semibold transition {{ request()->routeIs('reports.kpi') ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    KPI Pengajuan
                </a>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('reports.stock') }}" class="space-y-3">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Cari Barang
                        </label>

                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Kode atau nama barang"
                               class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Entitas
                        </label>

                        <select name="entity_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            <option value="">Semua Entitas Akses</option>

                            @foreach ($entities as $entity)
                                <option value="{{ $entity->id }}"
                                        @selected(
                                            (string) $entityId
                                            === (string) $entity->id
                                        )>
                                    {{ $entity->code }} - {{ $entity->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Gudang
                        </label>

                        <select name="warehouse_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            <option value="">Semua Gudang Akses</option>

                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                        @selected(
                                            (string) $warehouseId
                                            === (string) $warehouse->id
                                        )>
                                    {{ $warehouse->code }} - {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Kategori
                        </label>

                        <select name="category_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            <option value="">Semua Kategori</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                        @selected(
                                            (string) $categoryId
                                            === (string) $category->id
                                        )>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Kondisi Stok
                        </label>

                        <select name="stock_status"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            <option value="">Semua Kondisi</option>
                            <option value="low" @selected($stockStatus === 'low')>
                                Stok Menipis
                            </option>
                            <option value="safe" @selected($stockStatus === 'safe')>
                                Stok Aman
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <a href="{{ route('reports.stock') }}"
                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                        Reset
                    </a>

                    <button type="submit"
                            class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">
                        Filter
                    </button>

                    <a href="{{ route('reports.stock.export-excel', request()->query()) }}"
                       class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white">
                        Excel
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Data Stok</p>
                <p class="mt-1 text-lg font-bold text-slate-800">
                    {{ $totalItems }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Total Stok</p>
                <p class="mt-1 text-lg font-bold text-slate-800">
                    {{ $totalStock }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Stok Menipis</p>
                <p class="mt-1 text-lg font-bold text-red-600">
                    {{ $lowStockCount }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Lokasi Tampil</p>
                <p class="mt-1 text-lg font-bold text-sky-700">
                    {{ $totalLocations }}
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold text-slate-800">
                    Data Stok Barang
                </h2>

                <p class="text-[10px] text-slate-500">
                    Setiap baris mewakili satu kombinasi entitas, gudang, dan barang.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Gudang</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Kode</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Barang</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Jenis</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Kategori</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Satuan</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Stok</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Minimum</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($itemStocks as $stock)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                        {{ $stock->entity?->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-[11px] font-semibold text-slate-700">
                                        {{ $stock->warehouse?->name ?? '-' }}
                                    </p>

                                    <p class="text-[9px] text-slate-500">
                                        {{ $stock->warehouse?->code ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $stock->item?->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-xs font-semibold text-slate-800">
                                    {{ $stock->item?->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[10px] text-slate-600">
                                    {{ $stock->item?->type ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stock->item?->category?->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stock->item?->unit ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-right text-xs font-bold {{ $stock->is_low_stock ? 'text-red-700' : 'text-slate-800' }}">
                                    {{ $stock->current_stock ?? 0 }}
                                </td>

                                <td class="px-4 py-2.5 text-right text-[11px] text-slate-600">
                                    {{ $stock->minimum_stock ?? 0 }}
                                </td>

                                <td class="px-4 py-2.5">
                                    @if ($stock->is_low_stock)
                                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                                            Menipis
                                        </span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                            Aman
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11"
                                    class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Data stok tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
