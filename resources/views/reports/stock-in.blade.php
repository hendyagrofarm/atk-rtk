<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Laporan Stok Masuk
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Laporan transaksi penambahan stok barang berdasarkan entitas ANR/MAA.
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
                   class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200">
                    Stok
                </a>

                <a href="{{ route('reports.stock-in') }}"
                   class="inline-flex items-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm">
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
            <form method="GET" action="{{ route('reports.stock-in') }}">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-6">
                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Tanggal Awal
                        </label>
                        <input
                            type="date"
                            name="start_date"
                            value="{{ request('start_date') }}"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            Tanggal Akhir
                        </label>
                        <input
                            type="date"
                            name="end_date"
                            value="{{ request('end_date') }}"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

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

                    <div class="md:col-span-2 flex items-end gap-2">
                        <button type="submit"
                                class="inline-flex flex-1 items-center justify-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800">
                            Filter
                        </button>

                        <a href="{{ route('reports.stock-in') }}"
                           class="inline-flex flex-1 items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                            Reset
                        </a>

                        <a href="{{ route('reports.stock-in.export-excel', request()->query()) }}"
                           class="inline-flex flex-1 items-center justify-center rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-700">
                            Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">
                    Total Transaksi
                </p>
                <p class="mt-1 text-lg font-bold leading-6 text-slate-800">
                    {{ $totalTransactions }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">
                    Total Jumlah Masuk
                </p>
                <p class="mt-1 text-lg font-bold leading-6 text-emerald-700">
                    {{ $totalQuantity }}
                </p>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold leading-5 text-slate-800">
                    Data Stok Masuk
                </h2>
                <p class="text-[10px] leading-4 text-slate-500">
                    Hasil laporan stok masuk berdasarkan filter yang dipilih.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                No
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Tanggal
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Entitas
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Kode
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Barang
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Kategori
                            </th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Jumlah
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Supplier
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                User Input
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Keterangan
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($stockIns as $stockIn)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->date ? $stockIn->date->format('d/m/Y') : '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                        {{ $stockIn->entity->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $stockIn->item->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-xs font-semibold text-slate-800">
                                        {{ $stockIn->item->name ?? '-' }}
                                    </p>
                                    <p class="text-[10px] text-slate-500">
                                        {{ $stockIn->item->unit ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->item->category->name ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        +{{ $stockIn->quantity }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->supplier ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->user->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $stockIn->note ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Data stok masuk tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>