<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold leading-5 text-slate-800">
                    Riwayat Stok Masuk
                </h2>
                <p class="text-[10px] leading-4 text-slate-500">
                    Pantau riwayat penambahan stok barang berdasarkan entitas ANR/MAA.
                </p>
            </div>

            <a href="{{ route('stock-ins.create') }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-md bg-blue-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0 4-4m-4 4-4-4M4 16.5V18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1.5" />
                </svg>
                Tambah Stok
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

        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <form method="GET" action="{{ route('stock-ins.index') }}" class="grid gap-2 md:grid-cols-5">
                <div>
                    <label for="start_date" class="mb-1 block text-[10px] font-semibold text-slate-600">
                        Tanggal Awal
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        value="{{ request('start_date') }}"
                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                    >
                </div>

                <div>
                    <label for="end_date" class="mb-1 block text-[10px] font-semibold text-slate-600">
                        Tanggal Akhir
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        value="{{ request('end_date') }}"
                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                    >
                </div>

                <div>
                    <label for="entity_id" class="mb-1 block text-[10px] font-semibold text-slate-600">
                        Entitas
                    </label>

                    <select
                        name="entity_id"
                        id="entity_id"
                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                    >
                        <option value="">Semua Entitas</option>

                        @foreach ($entities as $entity)
                            <option value="{{ $entity->id }}" @selected(request('entity_id') == $entity->id)>
                                {{ $entity->code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="item_id" class="mb-1 block text-[10px] font-semibold text-slate-600">
                        Barang
                    </label>

                    <select
                        name="item_id"
                        id="item_id"
                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                    >
                        <option value="">Semua Barang</option>

                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>
                                {{ $item->code }} - {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
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
                        href="{{ route('stock-ins.index') }}"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 shadow-sm hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-xs font-semibold leading-5 text-slate-800">
                    Data Stok Masuk
                </h3>
                <p class="text-[10px] leading-4 text-slate-500">
                    Daftar transaksi penambahan stok barang.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Barang</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Kategori</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Jumlah</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Stok Entitas Saat Ini</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Supplier</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">User Input</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Keterangan</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($stockIns as $stockIn)
                            @php
                                $currentEntityStock = null;

                                if ($stockIn->item && $stockIn->entity) {
                                    $currentEntityStock = $stockIn->item->itemStocks->firstWhere('entity_id', $stockIn->entity_id);
                                }
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIns->firstItem() + $loop->index }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $stockIn->date ? $stockIn->date->format('d-m-Y') : '-' }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    @if ($stockIn->entity)
                                        <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                            {{ $stockIn->entity->code }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 8.25-9-5.25-9 5.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21M3 8.25v7.5L12 21" />
                                            </svg>
                                        </div>

                                        <div>
                                            <div class="text-xs font-semibold text-slate-800">
                                                {{ $stockIn->item->name ?? '-' }}
                                            </div>
                                            <div class="text-[10px] text-slate-500">
                                                {{ $stockIn->item->code ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->item->category->name ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        +{{ $stockIn->quantity }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                    {{ $currentEntityStock ? $currentEntityStock->current_stock : '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->supplier ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->user->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->note ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center">
                                    <div class="text-xs font-semibold text-slate-600">
                                        Belum ada data stok masuk.
                                    </div>
                                    <div class="mt-1 text-[10px] text-slate-400">
                                        Tambahkan stok masuk terlebih dahulu.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($stockIns->hasPages())
                <div class="border-t border-slate-200 px-4 py-3 text-xs">
                    {{ $stockIns->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>