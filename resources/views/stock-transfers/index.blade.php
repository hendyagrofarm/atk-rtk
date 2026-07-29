<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-sm font-semibold text-white">Mutasi Stok Antar Gudang</h1>
                <p class="text-[10px] text-white/80">
                    Riwayat perpindahan stok dari gudang asal ke gudang tujuan.
                </p>
            </div>

            <a href="{{ route('stock-transfers.create') }}"
               class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-700">
                Buat Mutasi
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

        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <form method="GET" action="{{ route('stock-transfers.index') }}">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">Tanggal Awal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                               class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                               class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">Entitas</label>
                        <select name="entity_id" class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                            <option value="">Semua Entitas Akses</option>
                            @foreach ($entities as $entity)
                                <option value="{{ $entity->id }}"
                                        @selected((string) request('entity_id') === (string) $entity->id)>
                                    {{ $entity->code }} - {{ $entity->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">Barang</label>
                        <select name="item_id" class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                            <option value="">Semua Barang</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}"
                                        @selected((string) request('item_id') === (string) $item->id)>
                                    {{ $item->code }} - {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">Gudang Asal</label>
                        <select name="source_warehouse_id" class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                            <option value="">Semua Gudang Asal</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                        @selected((string) request('source_warehouse_id') === (string) $warehouse->id)>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">Gudang Tujuan</label>
                        <select name="destination_warehouse_id" class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                            <option value="">Semua Gudang Tujuan</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                        @selected((string) request('destination_warehouse_id') === (string) $warehouse->id)>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Nomor mutasi / barang"
                               class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                    </div>

                    <div class="flex items-end gap-2">
                        <a href="{{ route('stock-transfers.index') }}"
                           class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-center text-[11px] font-semibold text-slate-700">
                            Reset
                        </a>
                        <button type="submit"
                                class="flex-1 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">
                            Filter
                        </button>
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
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Nomor Mutasi</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Tanggal</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Gudang Asal</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Gudang Tujuan</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Barang</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Jumlah</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">User</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Keterangan</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($stockTransfers as $transfer)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockTransfers->firstItem() + $loop->index }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $transfer->transfer_number }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $transfer->transfer_date?->format('d-m-Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-blue-700">
                                    {{ $transfer->entity?->code ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <p class="text-[11px] font-semibold text-red-700">
                                        {{ $transfer->sourceWarehouse?->name ?? '-' }}
                                    </p>
                                    <p class="text-[9px] text-slate-500">
                                        {{ $transfer->sourceWarehouse?->code ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-4 py-2.5">
                                    <p class="text-[11px] font-semibold text-emerald-700">
                                        {{ $transfer->destinationWarehouse?->name ?? '-' }}
                                    </p>
                                    <p class="text-[9px] text-slate-500">
                                        {{ $transfer->destinationWarehouse?->code ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-4 py-2.5">
                                    <p class="text-xs font-semibold text-slate-800">
                                        {{ $transfer->item?->name ?? '-' }}
                                    </p>
                                    <p class="text-[10px] text-slate-500">
                                        {{ $transfer->item?->code ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs font-bold text-slate-800">
                                    {{ $transfer->quantity }} {{ $transfer->item?->unit ?? '' }}
                                </td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $transfer->user?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $transfer->note ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Belum ada transaksi mutasi stok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($stockTransfers->hasPages())
                <div class="border-t border-slate-200 px-4 py-3 text-xs">
                    {{ $stockTransfers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
