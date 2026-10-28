<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold text-white">Barang Keluar</h1>
                <p class="text-[10px] text-white/80">Pengeluaran barang langsung ke karyawan oleh admin pemegang stok.</p>
            </div>

            <a href="{{ route('stock-outs.create') }}"
               class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                Catat Barang Keluar
            </a>
        </div>
    </x-slot>

    <div class="space-y-3">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('stock-outs.index') }}" class="space-y-3">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-5">
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">

                    <select name="entity_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Entitas Akses</option>
                        @foreach ($entities as $entity)
                            <option value="{{ $entity->id }}" @selected((string) request('entity_id') === (string) $entity->id)>{{ $entity->code }}</option>
                        @endforeach
                    </select>

                    <select name="warehouse_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Gudang Akses</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) request('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>

                    <input type="search" name="search" value="{{ request('search') }}"
                           placeholder="Cari nomor, penerima, divisi, barang..."
                           class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('stock-outs.index') }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">Reset</a>
                    <button class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">Filter</button>
                    <a href="{{ route('stock-outs.export-excel', request()->query()) }}" class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white">Excel</a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] text-slate-500">Total Transaksi</p>
                <p class="text-lg font-bold">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] text-slate-500">Total Unit Keluar</p>
                <p class="text-lg font-bold text-red-700">{{ number_format($totalUnits, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach (['Nomor', 'Tanggal', 'Gudang', 'Penerima', 'Barang', 'Dikeluarkan Oleh', 'Aksi'] as $heading)
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($stockOuts as $stockOut)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">{{ $stockOut->issue_number }}</span>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $stockOut->issue_date?->format('d/m/Y') }}</td>

                                <td class="px-4 py-2.5">
                                    <p class="text-[11px] font-semibold text-slate-700">{{ $stockOut->warehouse?->name ?? '-' }}</p>
                                    <p class="text-[9px] text-blue-700">{{ $stockOut->entity?->code ?? '-' }}</p>
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-[11px] font-semibold text-slate-800">{{ $stockOut->recipient_name }}</p>
                                    @if ($stockOut->recipient_department)
                                        <p class="text-[10px] text-slate-500">{{ $stockOut->recipient_department }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5">
                                    @foreach ($stockOut->items as $line)
                                        <p class="text-[11px] text-slate-700">
                                            {{ $line->item?->name ?? '-' }}
                                            <span class="font-bold text-red-700">-{{ number_format($line->quantity, 0, ',', '.') }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $line->item?->unit }}</span>
                                        </p>
                                    @endforeach
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $stockOut->issuer?->name ?? '-' }}</td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <a href="{{ route('stock-outs.show', $stockOut) }}"
                                       class="inline-flex rounded-md bg-indigo-50 px-2 py-1.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100">
                                        Bukti / Cetak
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Belum ada pengeluaran barang langsung pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($stockOuts->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $stockOuts->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
