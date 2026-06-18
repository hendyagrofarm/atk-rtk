<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Laporan Barang Keluar
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Laporan barang keluar berdasarkan pengajuan yang sudah disetujui per entitas ANR/MAA.
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
                   class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200">
                    Stok Masuk
                </a>

                <a href="{{ route('reports.requests') }}"
                   class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200">
                    Pengajuan
                </a>

                <a href="{{ route('reports.stock-out') }}"
                   class="inline-flex items-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm">
                    Barang Keluar
                </a>
            </div>
        </div>

        {{-- Filter --}}
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('reports.stock-out') }}">
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

                    <div>
                        <label class="mb-1 block text-[10px] font-semibold text-slate-600">
                            User
                        </label>
                        <select name="user_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="inline-flex flex-1 items-center justify-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800">
                            Filter
                        </button>

                        <a href="{{ route('reports.stock-out') }}"
                           class="inline-flex flex-1 items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                            Reset
                        </a>

                        <a href="{{ route('reports.stock-out.export-excel', request()->query()) }}"
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
                    Total Pengajuan Disetujui
                </p>
                <p class="mt-1 text-lg font-bold leading-6 text-slate-800">
                    {{ $totalApprovedRequests }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">
                    Total Barang Keluar
                </p>
                <p class="mt-1 text-lg font-bold leading-6 text-red-700">
                    {{ $totalStockOut }}
                </p>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold leading-5 text-slate-800">
                    Data Barang Keluar
                </h2>
                <p class="text-[10px] leading-4 text-slate-500">
                    Hasil laporan barang keluar berdasarkan filter yang dipilih.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">No Pengajuan</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal Keluar</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Pemohon</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Barang</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Jumlah Keluar</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Approver</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($requests as $requestItem)
                            @php
                                $rowspan = max($requestItem->details->count(), 1);
                            @endphp

                            @forelse($requestItem->details as $detail)
                                <tr class="hover:bg-slate-50">
                                    @if($loop->first)
                                        <td rowspan="{{ $rowspan }}" class="whitespace-nowrap px-4 py-2.5 align-top text-[11px] text-slate-600">
                                            {{ $loop->parent->iteration }}
                                        </td>

                                        <td rowspan="{{ $rowspan }}" class="whitespace-nowrap px-4 py-2.5 align-top">
                                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                                {{ $requestItem->request_number }}
                                            </span>
                                        </td>

                                        <td rowspan="{{ $rowspan }}" class="whitespace-nowrap px-4 py-2.5 align-top">
                                            <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                                {{ $requestItem->entity->code ?? '-' }}
                                            </span>
                                        </td>

                                        <td rowspan="{{ $rowspan }}" class="whitespace-nowrap px-4 py-2.5 align-top text-[11px] text-slate-600">
                                            {{ $requestItem->approved_at ? $requestItem->approved_at->format('d/m/Y H:i') : '-' }}
                                        </td>

                                        <td rowspan="{{ $rowspan }}" class="whitespace-nowrap px-4 py-2.5 align-top text-[11px] text-slate-600">
                                            {{ $requestItem->user->name ?? '-' }}
                                        </td>
                                    @endif

                                    <td class="px-4 py-2.5 align-top">
                                        <p class="text-xs font-semibold text-slate-800">
                                            {{ $detail->item->name ?? '-' }}
                                        </p>
                                        <p class="text-[10px] text-slate-500">
                                            {{ $detail->item->code ?? '-' }}
                                            @if($detail->item && $detail->item->category)
                                                | {{ $detail->item->category->name }}
                                            @endif
                                        </p>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-2.5 align-top text-right">
                                        <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                            -{{ $detail->approved_quantity ?? 0 }}
                                        </span>
                                    </td>

                                    @if($loop->first)
                                        <td rowspan="{{ $rowspan }}" class="whitespace-nowrap px-4 py-2.5 align-top text-[11px] text-slate-600">
                                            {{ $requestItem->approver->name ?? '-' }}
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                            {{ $requestItem->request_number }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                            {{ $requestItem->entity->code ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $requestItem->approved_at ? $requestItem->approved_at->format('d/m/Y H:i') : '-' }}
                                    </td>

                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $requestItem->user->name ?? '-' }}
                                    </td>

                                    <td colspan="2" class="px-4 py-2.5 text-[11px] text-slate-400">
                                        Tidak ada detail barang.
                                    </td>

                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $requestItem->approver->name ?? '-' }}
                                    </td>
                                </tr>
                            @endforelse
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Data barang keluar tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>