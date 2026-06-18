<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Dashboard Statistik Laporan
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Ringkasan data stok, pengajuan, dan barang keluar.
            </p>
        </div>
    </x-slot>

    <div class="space-y-3">
        {{-- Menu Laporan --}}
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="flex flex-wrap gap-1.5">
                <a href="{{ route('reports.dashboard') }}"
                   class="inline-flex items-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm">
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
                   class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200">
                    Barang Keluar
                </a>
            </div>
        </div>

        {{-- Ringkasan Stok --}}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Total Barang</p>
                <p class="mt-1 text-lg font-bold leading-6 text-slate-800">{{ $totalItems }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Total Stok</p>
                <p class="mt-1 text-lg font-bold leading-6 text-slate-800">{{ $totalStock }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Stok Menipis</p>
                <p class="mt-1 text-lg font-bold leading-6 text-red-600">{{ $lowStockCount }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Stok Masuk Bulan Ini</p>
                <p class="mt-1 text-lg font-bold leading-6 text-emerald-600">{{ $stockInThisMonth }}</p>
            </div>
        </div>

        {{-- Ringkasan Pengajuan --}}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Total Pengajuan</p>
                <p class="mt-1 text-lg font-bold leading-6 text-slate-800">{{ $totalRequests }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Pending</p>
                <p class="mt-1 text-lg font-bold leading-6 text-amber-600">{{ $pendingRequests }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Disetujui</p>
                <p class="mt-1 text-lg font-bold leading-6 text-emerald-600">{{ $approvedRequests }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] font-medium text-slate-500">Ditolak</p>
                <p class="mt-1 text-lg font-bold leading-6 text-red-600">{{ $rejectedRequests }}</p>
            </div>
        </div>

        {{-- Ringkasan Barang Keluar --}}
        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <p class="text-[10px] font-medium text-slate-500">Total Barang Keluar Bulan Ini</p>
            <p class="mt-1 text-lg font-bold leading-6 text-blue-600">{{ $stockOutThisMonth }}</p>
        </div>

        {{-- Grafik --}}
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="mb-3 text-xs font-semibold text-slate-800">
                    Grafik Status Pengajuan
                </h3>
                <div class="h-56">
                    <canvas id="requestStatusChart"></canvas>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="mb-3 text-xs font-semibold text-slate-800">
                    Stok Masuk vs Barang Keluar
                </h3>
                <div class="h-56">
                    <canvas id="stockMovementChart"></canvas>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="mb-3 text-xs font-semibold text-slate-800">
                    5 Barang Stok Terendah
                </h3>
                <div class="h-56">
                    <canvas id="lowestStockChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tabel Bawah --}}
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            {{-- 5 Barang Stok Terendah --}}
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-xs font-semibold text-slate-800">
                        5 Barang dengan Stok Terendah
                    </h3>
                    <p class="text-[10px] text-slate-500">
                        Prioritas barang yang perlu dipantau.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">No</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Barang</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Kategori</th>
                                <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Stok</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($lowestStockItems as $item)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-4 py-2.5">
                                        <p class="text-xs font-semibold leading-5 text-slate-800">
                                            {{ $item->name }}
                                        </p>
                                        <p class="text-[10px] leading-4 text-slate-500">
                                            {{ $item->code }} | {{ $item->type }}
                                        </p>
                                    </td>

                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $item->category->name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-2.5 text-right">
                                        @if($item->current_stock <= $item->minimum_stock)
                                            <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                                {{ $item->current_stock }}
                                            </span>
                                        @else
                                            <span class="text-xs font-semibold text-slate-700">
                                                {{ $item->current_stock }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                        Data barang belum tersedia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pengajuan Terbaru --}}
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-xs font-semibold text-slate-800">
                        Pengajuan Terbaru
                    </h3>
                    <p class="text-[10px] text-slate-500">
                        Riwayat pengajuan terakhir.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">No</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">User</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($latestRequests as $requestItem)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-4 py-2.5">
                                        <p class="text-xs font-semibold leading-5 text-slate-800">
                                            {{ $requestItem->request_number ?? '-' }}
                                        </p>
                                        <p class="text-[10px] leading-4 text-slate-500">
                                            {{ $requestItem->request_date ? $requestItem->request_date->format('d-m-Y') : '-' }}
                                        </p>
                                    </td>

                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $requestItem->user->name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-2.5">
                                        @if($requestItem->status === 'pending')
                                            <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                                Pending
                                            </span>
                                        @elseif(in_array($requestItem->status, ['approved', 'disetujui']))
                                            <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                {{ ucfirst($requestItem->status) }}
                                            </span>
                                        @elseif(in_array($requestItem->status, ['rejected', 'ditolak']))
                                            <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                                {{ ucfirst($requestItem->status) }}
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                {{ $requestItem->status }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                        Data pengajuan belum tersedia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const pendingRequests = @json($pendingRequests);
        const approvedRequests = @json($approvedRequests);
        const rejectedRequests = @json($rejectedRequests);

        const stockInThisMonth = @json($stockInThisMonth);
        const stockOutThisMonth = @json($stockOutThisMonth);

        const lowestStockLabels = @json($lowestStockItems->pluck('name'));
        const lowestStockData = @json($lowestStockItems->pluck('current_stock'));

        new Chart(document.getElementById('requestStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Disetujui', 'Ditolak'],
                datasets: [{
                    data: [pendingRequests, approvedRequests, rejectedRequests],
                    backgroundColor: ['#facc15', '#22c55e', '#ef4444'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        new Chart(document.getElementById('stockMovementChart'), {
            type: 'bar',
            data: {
                labels: ['Stok Masuk', 'Barang Keluar'],
                datasets: [{
                    label: 'Jumlah',
                    data: [stockInThisMonth, stockOutThisMonth],
                    backgroundColor: ['#22c55e', '#3b82f6'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('lowestStockChart'), {
            type: 'bar',
            data: {
                labels: lowestStockLabels,
                datasets: [{
                    label: 'Stok',
                    data: lowestStockData,
                    backgroundColor: '#ef4444',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</x-app-layout>