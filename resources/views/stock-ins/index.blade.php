<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold leading-5 text-white">
                    Riwayat Stok Masuk
                </h2>

                <p class="text-[10px] leading-4 text-white/80">
                    Pantau penambahan stok berdasarkan entitas dan gudang yang boleh diakses.
                </p>
            </div>

            <a href="{{ route('stock-ins.create') }}"
               class="inline-flex items-center justify-center rounded-md bg-blue-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-blue-800">
                Tambah Stok
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

        @if (! $isSuperAdmin)
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-md border border-sky-200 bg-sky-50 px-3 py-2">
                    <p class="text-[9px] font-bold uppercase tracking-wide text-sky-600">
                        Akses Entitas
                    </p>

                    <div class="mt-1 flex flex-wrap gap-1">
                        @forelse ($entities as $entity)
                            <span class="rounded-full bg-white px-2 py-0.5 text-[9px] font-semibold text-sky-700 ring-1 ring-sky-200">
                                {{ $entity->code }}
                            </span>
                        @empty
                            <span class="text-[10px] text-red-600">Belum diatur</span>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-md border border-violet-200 bg-violet-50 px-3 py-2">
                    <p class="text-[9px] font-bold uppercase tracking-wide text-violet-600">
                        Akses Gudang
                    </p>

                    <div class="mt-1 flex flex-wrap gap-1">
                        @forelse ($warehouses as $warehouse)
                            <span class="rounded-full bg-white px-2 py-0.5 text-[9px] font-semibold text-violet-700 ring-1 ring-violet-200">
                                {{ $warehouse->name }}
                            </span>
                        @empty
                            <span class="text-[10px] text-red-600">Belum diatur</span>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <form method="GET" action="{{ route('stock-ins.index') }}" class="space-y-3">
                <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Tanggal Awal
                        </label>

                        <input type="date"
                               name="start_date"
                               value="{{ request('start_date') }}"
                               class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Tanggal Akhir
                        </label>

                        <input type="date"
                               name="end_date"
                               value="{{ request('end_date') }}"
                               class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Entitas
                        </label>

                        <select name="entity_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                            <option value="">Semua Entitas Akses</option>

                            @foreach ($entities as $entity)
                                <option value="{{ $entity->id }}"
                                        @selected(
                                            (string) request('entity_id')
                                            === (string) $entity->id
                                        )>
                                    {{ $entity->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Gudang
                        </label>

                        <select name="warehouse_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                            <option value="">Semua Gudang Akses</option>

                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                        @selected(
                                            (string) request('warehouse_id')
                                            === (string) $warehouse->id
                                        )>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Barang
                        </label>

                        <select name="item_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                            <option value="">Semua Barang</option>

                            @foreach ($items as $item)
                                <option value="{{ $item->id }}"
                                        @selected(
                                            (string) request('item_id')
                                            === (string) $item->id
                                        )>
                                    {{ $item->code }} - {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('stock-ins.index') }}"
                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600">
                        Reset
                    </a>

                    <button type="submit"
                            class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-xs font-semibold text-slate-800">Data Stok Masuk</h3>
                <p class="text-[10px] text-slate-500">Daftar transaksi penambahan stok barang.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Tanggal</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Gudang</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Barang</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Kategori</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Jumlah</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Stok Lokasi</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Supplier</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">User Input</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Keterangan</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($stockIns as $stockIn)
                            @php
                                $currentLocationStock = $stockIn->item?->itemStocks
                                    ?->first(function ($stock) use ($stockIn) {
                                        return
                                            (int) $stock->entity_id === (int) $stockIn->entity_id
                                            && (int) $stock->warehouse_id === (int) $stockIn->warehouse_id;
                                    });
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIns->firstItem() + $loop->index }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->date?->format('d-m-Y') ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] font-semibold text-blue-700">
                                    {{ $stockIn->entity?->code ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-[11px] font-semibold text-slate-700">
                                        {{ $stockIn->warehouse?->name ?? '-' }}
                                    </p>
                                    <p class="text-[9px] text-slate-500">
                                        {{ $stockIn->warehouse?->code ?? '' }}
                                    </p>
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-xs font-semibold text-slate-800">
                                        {{ $stockIn->item?->name ?? '-' }}
                                    </p>
                                    <p class="text-[10px] text-slate-500">
                                        {{ $stockIn->item?->code ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->item?->category?->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-right text-[11px] font-semibold text-emerald-700">
                                    +{{ $stockIn->quantity }}
                                </td>

                                <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                    {{ $currentLocationStock?->current_stock ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->supplier ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->user?->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->note ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-8 text-center text-xs text-slate-500">
                                    Belum ada data stok masuk.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($stockIns->hasPages())
                <div class="border-t border-slate-200 px-4 py-3 text-xs">
                    {{ $stockIns->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
