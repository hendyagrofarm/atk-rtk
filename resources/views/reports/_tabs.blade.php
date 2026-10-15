@php
    $reportTabs = [
        ['route' => 'reports.dashboard', 'label' => 'Dashboard'],
        ['route' => 'reports.stock', 'label' => 'Stok'],
        ['route' => 'reports.stock-in', 'label' => 'Stok Masuk'],
        ['route' => 'reports.requests', 'label' => 'Pengajuan'],
        ['route' => 'reports.stock-out', 'label' => 'Barang Keluar'],
        ['route' => 'reports.stock-transfers', 'label' => 'Mutasi Stok'],
        ['route' => 'reports.kpi', 'label' => 'KPI Pengajuan'],
    ];
@endphp

<div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
    <div class="flex flex-wrap gap-1.5">
        @foreach ($reportTabs as $tab)
            <a href="{{ route($tab['route']) }}"
               class="inline-flex items-center rounded-md px-3 py-1.5 text-[11px] font-semibold transition {{ request()->routeIs($tab['route']) ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</div>
