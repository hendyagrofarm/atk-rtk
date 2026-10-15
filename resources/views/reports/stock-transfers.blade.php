<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold text-white">Laporan Mutasi Stok</h1>
            <p class="text-[10px] text-white/80">
                Laporan perpindahan stok antar gudang berdasarkan hak akses lokasi.
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
            <form method="GET" action="{{ route('reports.stock-transfers') }}">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-4">
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="rounded-md border-slate-300 px-3 py-1.5 text-xs">

                    <select name="entity_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Entitas Akses</option>
                        @foreach ($entities as $entity)
                            <option value="{{ $entity->id }}" @selected((string) request('entity_id') === (string) $entity->id)>
                                {{ $entity->code }} - {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="item_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Barang</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" @selected((string) request('item_id') === (string) $item->id)>
                                {{ $item->code }} - {{ $item->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="source_warehouse_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Gudang Asal</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) request('source_warehouse_id') === (string) $warehouse->id)>
                                {{ $warehouse->code }} - {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="destination_warehouse_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Gudang Tujuan</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) request('destination_warehouse_id') === (string) $warehouse->id)>
                                {{ $warehouse->code }} - {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="user_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua User</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>

                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nomor mutasi, barang, keterangan"
                           class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                </div>

                <div class="mt-3 flex justify-end gap-2">
                    <a href="{{ route('reports.stock-transfers') }}"
                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                        Reset
                    </a>

                    <button type="submit"
                            class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">
                        Filter
                    </button>

                    <a href="{{ route('reports.stock-transfers.export-excel', request()->query()) }}"
                       class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white">
                        Excel
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Total Transaksi</p>
                <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($totalTransactions) }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Total Jumlah Mutasi</p>
                <p class="mt-1 text-lg font-bold text-blue-700">{{ number_format($totalQuantity) }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Total Jalur Mutasi</p>
                <p class="mt-1 text-lg font-bold text-emerald-700">{{ number_format($totalRoutes) }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach (['No','Nomor Mutasi','Tanggal','Entitas','Gudang Asal','Gudang Tujuan','Barang','Jumlah','User','Keterangan'] as $heading)
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($transfers as $transfer)
                            <tr>
                                <td class="px-4 py-2.5 text-[11px]">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2.5 text-[10px] font-semibold">{{ $transfer->transfer_number }}</td>
                                <td class="px-4 py-2.5 text-[11px]">{{ $transfer->transfer_date?->format('d-m-Y') ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-blue-700">{{ $transfer->entity?->code ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-[11px] text-red-700">{{ $transfer->sourceWarehouse?->name ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-[11px] text-emerald-700">{{ $transfer->destinationWarehouse?->name ?? '-' }}</td>
                                <td class="px-4 py-2.5">
                                    <p class="text-xs font-semibold">{{ $transfer->item?->name ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $transfer->item?->code ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs font-bold text-blue-700">
                                    {{ number_format($transfer->quantity) }} {{ $transfer->item?->unit ?? '' }}
                                </td>
                                <td class="px-4 py-2.5 text-[11px]">{{ $transfer->user?->name ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-[11px]">{{ $transfer->note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Data mutasi stok tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
