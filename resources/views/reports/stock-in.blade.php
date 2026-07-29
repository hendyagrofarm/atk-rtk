<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold text-white">Laporan Stok Masuk</h1>
            <p class="text-[10px] text-white/80">Laporan berdasarkan entitas dan gudang tujuan.</p>
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
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('reports.stock-in') }}" class="space-y-3">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-6">
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">

                    <select name="entity_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Entitas Akses</option>
                        @foreach ($entities as $entity)
                            <option value="{{ $entity->id }}" @selected((string) $entityId === (string) $entity->id)>
                                {{ $entity->code }} - {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="warehouse_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Gudang Akses</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>
                                {{ $warehouse->code }} - {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="category_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button class="flex-1 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">Filter</button>
                        <a href="{{ route('reports.stock-in') }}" class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-center text-[11px] font-semibold text-slate-700">Reset</a>
                        <a href="{{ route('reports.stock-in.export-excel', request()->query()) }}" class="flex-1 rounded-md bg-emerald-600 px-3 py-1.5 text-center text-[11px] font-semibold text-white">Excel</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Total Transaksi</p>
                <p class="mt-1 text-lg font-bold text-slate-800">{{ $totalTransactions }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Total Jumlah Masuk</p>
                <p class="mt-1 text-lg font-bold text-emerald-700">{{ $totalQuantity }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach (['No','Tanggal','Entitas','Gudang','Kode','Barang','Kategori','Jumlah','Supplier','User Input','Keterangan'] as $heading)
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($stockIns as $stockIn)
                            <tr>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $stockIn->date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-[11px] font-semibold text-blue-700">{{ $stockIn->entity?->code ?? '-' }}</td>
                                <td class="px-4 py-2.5">
                                    <p class="text-[11px] font-semibold text-slate-700">{{ $stockIn->warehouse?->name ?? '-' }}</p>
                                    <p class="text-[9px] text-slate-500">{{ $stockIn->warehouse?->code ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $stockIn->item?->code ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-xs font-semibold text-slate-800">{{ $stockIn->item?->name ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $stockIn->item?->category?->name ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-right text-xs font-bold text-emerald-700">+{{ $stockIn->quantity }}</td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $stockIn->supplier ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $stockIn->user?->name ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $stockIn->note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="px-4 py-8 text-center text-[11px] text-slate-500">Data stok masuk tidak ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
