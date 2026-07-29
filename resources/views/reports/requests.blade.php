<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold text-white">Laporan Pengajuan Barang</h1>
            <p class="text-[10px] text-white/80">Laporan berdasarkan entitas dan gudang sumber.</p>
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
            <form method="GET" action="{{ route('reports.requests') }}" class="space-y-3">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-7">
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">

                    <select name="entity_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Entitas Akses</option>
                        @foreach ($entities as $entity)
                            <option value="{{ $entity->id }}" @selected((string) $entityId === (string) $entity->id)>{{ $entity->code }}</option>
                        @endforeach
                    </select>

                    <select name="warehouse_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Gudang Akses</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>

                    <select name="category_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                    </select>

                    <select name="user_id" class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua User</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('reports.requests') }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">Reset</a>
                    <button class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">Filter</button>
                    <a href="{{ route('reports.requests.export-excel', request()->query()) }}" class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white">Excel</a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-3"><p class="text-[10px] text-slate-500">Total Pengajuan</p><p class="text-lg font-bold">{{ $totalRequests }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-3"><p class="text-[10px] text-slate-500">Barang Diminta</p><p class="text-lg font-bold">{{ $totalItemsRequested }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-3"><p class="text-[10px] text-slate-500">Barang Disetujui</p><p class="text-lg font-bold text-emerald-700">{{ $totalItemsApproved }}</p></div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach (['No','No Pengajuan','Entitas','Gudang','Tanggal','Pemohon','Barang','Diminta','Disetujui','Status','Approver'] as $heading)
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $requestItem)
                            @php
                                $rowspan = max($requestItem->details->count(), 1);
                                $statusText = match ($requestItem->status) {
                                    'approved', 'disetujui' => 'Disetujui',
                                    'rejected', 'ditolak' => 'Ditolak',
                                    default => 'Pending',
                                };
                            @endphp

                            @forelse ($requestItem->details as $detail)
                                <tr>
                                    @if ($loop->first)
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-2.5 align-top text-[11px]">{{ $loop->parent->iteration }}</td>
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-2.5 align-top text-[10px] font-semibold">{{ $requestItem->request_number }}</td>
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-2.5 align-top text-[11px] font-semibold text-blue-700">{{ $requestItem->entity?->code ?? '-' }}</td>
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-2.5 align-top">
                                            <p class="text-[11px] font-semibold">{{ $requestItem->warehouse?->name ?? '-' }}</p>
                                            <p class="text-[9px] text-slate-500">{{ $requestItem->warehouse?->code ?? '-' }}</p>
                                        </td>
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-2.5 align-top text-[11px]">{{ $requestItem->request_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-2.5 align-top text-[11px]">{{ $requestItem->user?->name ?? '-' }}</td>
                                    @endif

                                    <td class="px-4 py-2.5">
                                        <p class="text-xs font-semibold">{{ $detail->item?->name ?? $detail->custom_item_name ?? '-' }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $detail->item?->code ?? 'CUSTOM' }}</p>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-xs font-semibold">{{ $detail->quantity }}</td>
                                    <td class="px-4 py-2.5 text-right text-xs font-semibold text-emerald-700">{{ $detail->approved_quantity ?? 0 }}</td>

                                    @if ($loop->first)
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-2.5 align-top text-[11px]">{{ $statusText }}</td>
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-2.5 align-top text-[11px]">{{ $requestItem->approver?->name ?? '-' }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="11" class="px-4 py-3 text-center text-[11px] text-slate-500">Pengajuan tidak mempunyai detail barang.</td></tr>
                            @endforelse
                        @empty
                            <tr><td colspan="11" class="px-4 py-8 text-center text-[11px] text-slate-500">Data pengajuan tidak ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
