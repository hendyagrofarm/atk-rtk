<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold text-white">Laporan KPI Pengajuan</h1>
            <p class="text-[10px] text-white/80">Tingkat pemenuhan, kekurangan stok, dan alasan penolakan pengajuan dari stok.</p>
        </div>
    </x-slot>

    @php
        $rate = fn ($value) => $value === null ? '-' : number_format($value, 1, ',', '.') . '%';
        $rateColor = function ($value) {
            if ($value === null) {
                return 'text-slate-500';
            }

            return $value >= 90 ? 'text-emerald-700' : ($value >= 70 ? 'text-amber-600' : 'text-red-700');
        };
        $coverage = $summary['detail_total'] > 0
            ? round($summary['detail_with_snapshot'] / $summary['detail_total'] * 100)
            : null;
    @endphp

    <div class="space-y-3">
        @include('reports._tabs')

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            @if ($errors->any())
                <div class="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="GET" action="{{ route('reports.kpi') }}" class="space-y-3">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-5">
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
                            <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('reports.kpi') }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">Reset</a>
                    <button class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">Filter</button>
                    <a href="{{ route('reports.kpi.export-excel', request()->query()) }}" class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white">Excel</a>
                </div>
            </form>
        </div>

        {{-- KPI utama --}}
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] font-semibold uppercase text-slate-500">Tingkat Pemenuhan Unit</p>
                <p class="mt-1 text-xl font-bold {{ $rateColor($summary['fulfillment_rate']) }}">{{ $rate($summary['fulfillment_rate']) }}</p>
                <p class="text-[10px] text-slate-500">{{ number_format($summary['units_approved']) }} dari {{ number_format($summary['units_requested_processed']) }} unit diminta</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] font-semibold uppercase text-slate-500">Disetujui Penuh</p>
                <p class="mt-1 text-xl font-bold {{ $rateColor($summary['full_approval_rate']) }}">{{ $rate($summary['full_approval_rate']) }}</p>
                <p class="text-[10px] text-slate-500">dari {{ $summary['processed'] }} pengajuan diproses</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] font-semibold uppercase text-slate-500">Unit Kekurangan Stok</p>
                <p class="mt-1 text-xl font-bold text-red-700">{{ number_format($summary['shortage_units']) }}</p>
                <p class="text-[10px] text-slate-500">{{ $summary['shortage_lines'] }} barang kurang, {{ $summary['empty_lines'] }} saat stok kosong</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] font-semibold uppercase text-slate-500">Rata-rata Waktu Proses</p>
                <p class="mt-1 text-xl font-bold text-slate-800">
                    {{ $summary['avg_processing_hours'] === null ? '-' : number_format($summary['avg_processing_hours'], 1, ',', '.') . ' jam' }}
                </p>
                <p class="text-[10px] text-slate-500">dari pengajuan dibuat sampai diproses</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] text-slate-500">Total Pengajuan</p>
                <p class="text-lg font-bold">{{ $summary['total_requests'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] text-slate-500">Menunggu</p>
                <p class="text-lg font-bold text-amber-600">{{ $summary['pending'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] text-slate-500">Disetujui</p>
                <p class="text-lg font-bold text-emerald-700">{{ $summary['approved'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[10px] text-slate-500">Ditolak</p>
                <p class="text-lg font-bold text-red-700">{{ $summary['rejected'] }}</p>
            </div>
        </div>

        @if ($summary['detail_total'] > 0 && $summary['detail_with_snapshot'] < $summary['detail_total'])
            <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-800">
                Angka kekurangan stok hanya dihitung dari barang yang punya catatan stok saat pengajuan
                ({{ $summary['detail_with_snapshot'] }} dari {{ $summary['detail_total'] }} barang, {{ $coverage }}%).
                @if ($summary['snapshot_since'])
                    Catatan tersedia sejak {{ $summary['snapshot_since'] }}.
                @else
                    Belum ada pengajuan yang memiliki catatan stok pada periode ini.
                @endif
                Pengajuan sebelum itu tetap masuk ke tingkat pemenuhan, tetapi tidak ke angka kekurangan.
            </div>
        @endif

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-3">
            {{-- Barang paling sering kurang --}}
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-bold text-slate-800">Barang Paling Sering Kurang</h3>
                    <p class="text-[11px] text-slate-500">Hanya barang yang pernah kurang, diurutkan dari unit kekurangan terbanyak (10 teratas). Seluruh barang ada di Excel.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                @foreach (['No', 'Barang', 'Diminta', 'Disetujui', 'Kali Kurang', 'Unit Kurang', 'Kali Kosong'] as $heading)
                                    <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500 {{ $loop->first || $loop->index === 1 ? '' : 'text-right' }}">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse (array_slice(array_filter($perItem, fn ($row) => $row['shortage_lines'] > 0), 0, 10) as $row)
                                <tr>
                                    <td class="px-3 py-2.5 text-[11px] text-slate-600">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2.5">
                                        <p class="text-xs font-semibold text-slate-800">{{ $row['name'] }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $row['code'] }} | {{ $row['category'] }}</p>
                                    </td>
                                    <td class="px-3 py-2.5 text-right text-xs">{{ number_format($row['units_requested']) }} {{ $row['unit'] }}</td>
                                    <td class="px-3 py-2.5 text-right text-xs text-emerald-700">{{ number_format($row['units_approved']) }}</td>
                                    <td class="px-3 py-2.5 text-right text-xs">{{ $row['shortage_lines'] }}</td>
                                    <td class="px-3 py-2.5 text-right text-xs font-bold {{ $row['shortage_units'] > 0 ? 'text-red-700' : 'text-slate-500' }}">{{ number_format($row['shortage_units']) }}</td>
                                    <td class="px-3 py-2.5 text-right text-xs">{{ $row['empty_lines'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-xs text-slate-500">Belum ada catatan kekurangan stok pada filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Alasan penolakan --}}
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-bold text-slate-800">Alasan Penolakan</h3>
                    <p class="text-[11px] text-slate-500">Dari {{ $summary['rejected'] }} pengajuan ditolak.</p>
                </div>

                <div class="space-y-2 p-4">
                    @forelse ($rejectionReasons as $row)
                        @php($share = $summary['rejected'] > 0 ? round($row['total'] / $summary['rejected'] * 100) : 0)

                        <div>
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-700">{{ $row['label'] }}</span>
                                <span class="text-slate-600">{{ $row['total'] }} ({{ $share }}%)</span>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-red-500" style="width: {{ $share }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-slate-500">Belum ada pengajuan yang ditolak.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Per lokasi --}}
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-bold text-slate-800">Pemenuhan per Entitas dan Gudang</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach (['Entitas', 'Gudang', 'Pengajuan', 'Ditolak', 'Unit Diminta', 'Unit Disetujui', 'Pemenuhan', 'Unit Kurang'] as $heading)
                                <th class="px-3 py-2.5 text-[10px] font-bold uppercase text-slate-500 {{ $loop->index < 2 ? 'text-left' : 'text-right' }}">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($perLocation as $row)
                            <tr>
                                <td class="px-3 py-2.5 text-xs font-semibold text-blue-700">{{ $row['entity'] }}</td>
                                <td class="px-3 py-2.5 text-xs">{{ $row['warehouse'] }}</td>
                                <td class="px-3 py-2.5 text-right text-xs">{{ $row['requests'] }}</td>
                                <td class="px-3 py-2.5 text-right text-xs">{{ $row['rejected'] }}</td>
                                <td class="px-3 py-2.5 text-right text-xs">{{ number_format($row['units_requested']) }}</td>
                                <td class="px-3 py-2.5 text-right text-xs text-emerald-700">{{ number_format($row['units_approved']) }}</td>
                                <td class="px-3 py-2.5 text-right text-xs font-bold {{ $rateColor($row['fulfillment_rate']) }}">{{ $rate($row['fulfillment_rate']) }}</td>
                                <td class="px-3 py-2.5 text-right text-xs {{ $row['shortage_units'] > 0 ? 'font-bold text-red-700' : '' }}">{{ number_format($row['shortage_units']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-xs text-slate-500">Belum ada data pengajuan pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-[10px] leading-4 text-slate-600">
            <p class="font-semibold text-slate-700">Cara membaca angka</p>
            <p>Tingkat pemenuhan = unit disetujui dibagi unit diminta, dihitung dari pengajuan yang sudah diproses. Pengajuan ditolak dihitung 0 unit terpenuhi. Pengajuan yang masih menunggu belum dihitung.</p>
            <p>Kekurangan stok = jumlah diminta dikurangi stok lokasi saat pengajuan dibuat. Hanya pengajuan dari stok yang dihitung; pengajuan barang baru/custom tidak memakai stok.</p>
        </div>
    </div>
</x-app-layout>
