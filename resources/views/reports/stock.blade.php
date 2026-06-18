<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Laporan Stok Barang
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Laporan posisi stok barang ATK/RTK berdasarkan entitas ANR/MAA.
            </p>
        </div>
    </x-slot>

    <div class="space-y-3">
        {{-- Menu Laporan --}}
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="flex flex-wrap gap-1.5">
                <a href="{{ route('reports.dashboard') }}"
                   class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200">
                    Dashboard
                </a>

                <a href="{{ route('reports.stock') }}"
                   class="inline-flex items-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm">
                    Stok
                </a>

                <a href="{{ route('reports.stock-in') }}"
                   class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200">
                    Stok Masuk
                </a>

                <a href="{{ route('reports.requests') }}"
                   class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200">
                    Pengajuan
                </a>

                <a href="{{ route('reports.stock-out') }}"
                   class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200">
                    Barang Keluar
                </a>
            </div>
        </div>

        {{-- Filter --}}
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('reports.stock') }}">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Entitas
                        </label>
                        <select name="entity_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Entitas</option>
                            @foreach($entities as $entity)
                                <option value="{{ $entity->id }}" {{ request('entity_id') == $entity->id ? 'selected' : '' }}>
                                    {{ $entity->code }} - {{ $entity->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Kategori
                        </label>
                        <select name="category_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Status Stok
                        </label>
                        <select name="stock_status"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Status</option>
                            <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Stok Menipis</option>
                            <option value="safe" {{ request('stock_status') == 'safe' ? 'selected' : '' }}>Stok Aman</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="inline-flex flex-1 items-center justify-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800">
                            Filter
                        </button>

                        <a href="{{ route('reports.stock') }}"
                           class="inline-flex flex-1 items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                            Reset
                        </a>

                        <a href="{{ route('reports.stock.export-excel', request()->query()) }}"
                           class="inline-flex flex-1 items-center justify-center rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-700">
                            Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Total Data Stok</p>
                <p class="mt-1 text-lg font-bold leading-6 text-slate-800">{{ $totalItems }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Total Stok</p>
                <p class="mt-1 text-lg font-bold leading-6 text-slate-800">{{ $totalStock }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Stok Menipis</p>
                <p class="mt-1 text-lg font-bold leading-6 text-red-600">{{ $lowStockCount }}</p>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold leading-5 text-slate-800">
                    Data Stok Barang
                </h2>
                <p class="text-[10px] leading-4 text-slate-500">
                    Hasil laporan berdasarkan filter yang dipilih.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Kode</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Barang</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Jenis</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Kategori</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Satuan</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Stok</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Minimum</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($itemStocks as $stock)
                            @php
                                $isLowStock = ($stock->current_stock ?? 0) <= ($stock->minimum_stock ?? 0);
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                        {{ $stock->entity->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $stock->item->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-xs font-semibold text-slate-800">
                                    {{ $stock->item->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        {{ $stock->item->type ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stock->item->category->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stock->item->unit ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-800">
                                    {{ $stock->current_stock ?? 0 }}
                                </td>

                                <td class="px-4 py-2.5 text-right text-[11px] text-slate-600">
                                    {{ $stock->minimum_stock ?? 0 }}
                                </td>

                                <td class="px-4 py-2.5">
                                    @if($isLowStock)
                                        <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                            Stok Menipis
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            Aman
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-[11px] text-slate-500">
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