<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-[13px] font-semibold leading-4 text-white">
                Dashboard Portal ATK-RTK
            </h2>
            <p class="text-[10px] leading-3 text-white/80">
                Ringkasan akses, stok barang, dan pengajuan terbaru.
            </p>
        </div>
    </x-slot>

    @php
        $user = Auth::user();
        $role = $user->role ?? '-';

        $quickMenus = [];

        if ($role === 'admin') {
            $quickMenus = [
                [
                    'title' => 'Manajemen User',
                    'description' => 'Kelola akun pengguna.',
                    'route' => route('users.index'),
                    'color' => 'bg-[#08B05A]',
                ],
                [
                    'title' => 'Kategori Barang',
                    'description' => 'Kelola kategori ATK/RTK.',
                    'route' => route('categories.index'),
                    'color' => 'bg-[#F4B400]',
                ],
                [
                    'title' => 'Data Barang',
                    'description' => 'Kelola item dan stok.',
                    'route' => route('items.index'),
                    'color' => 'bg-[#1FA8C9]',
                ],
                [
                    'title' => 'Stok Masuk',
                    'description' => 'Catat penambahan stok.',
                    'route' => route('stock-ins.index'),
                    'color' => 'bg-[#08B05A]',
                ],
                [
                    'title' => 'Approval',
                    'description' => 'Proses pengajuan barang.',
                    'route' => route('approvals.index'),
                    'color' => 'bg-[#E74C3C]',
                ],
                [
                    'title' => 'Laporan',
                    'description' => 'Lihat rekap data.',
                    'route' => route('reports.dashboard'),
                    'color' => 'bg-[#1FA8C9]',
                ],
            ];
        } elseif ($role === 'staff') {
            $quickMenus = [
                [
                    'title' => 'Pengajuan Saya',
                    'description' => 'Lihat pengajuan barang.',
                    'route' => route('staff.requests.index'),
                    'color' => 'bg-[#1FA8C9]',
                ],
                [
                    'title' => 'Buat Pengajuan',
                    'description' => 'Ajukan kebutuhan ATK/RTK.',
                    'route' => route('staff.requests.create'),
                    'color' => 'bg-[#08B05A]',
                ],
            ];
        } elseif ($role === 'approver') {
            $quickMenus = [
                [
                    'title' => 'Approval Pengajuan',
                    'description' => 'Proses pengajuan staff.',
                    'route' => route('approvals.index'),
                    'color' => 'bg-[#E74C3C]',
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Data Dashboard
        |--------------------------------------------------------------------------
        | Tabel stok terendah dan pengajuan terbaru hanya ditampilkan untuk
        | admin dan approver.
        */
        $lowStockItems = collect();
        $latestRequests = collect();

        if (in_array($role, ['admin', 'approver'])) {
            if (class_exists(\App\Models\Item::class)) {
                $lowStockItems = \App\Models\Item::with('category')
                    ->orderBy('current_stock', 'asc')
                    ->orderBy('name', 'asc')
                    ->limit(5)
                    ->get();
            }

            if (class_exists(\App\Models\Request::class)) {
                $latestRequests = \App\Models\Request::with('user')
                    ->orderByDesc('request_date')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get();
            }
        }

        $statusBadge = function ($status) {
            return match ($status) {
                'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
                'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'rejected' => 'bg-red-50 text-red-700 ring-red-200',
                default => 'bg-slate-50 text-slate-700 ring-slate-200',
            };
        };

        $statusLabel = function ($status) {
            return match ($status) {
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => ucfirst($status ?? '-'),
            };
        };
    @endphp

    <div class="space-y-3">
        {{-- Compact Summary --}}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-md bg-[#08B05A] text-white">
                        <svg class="h-4.5 w-4.5" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7.5 12 3 4 7.5m16 0-8 4.5m8-4.5v9L12 21m0-9L4 7.5m8 4.5v9M4 7.5v9L12 21" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            Aplikasi
                        </p>
                        <p class="truncate text-sm font-bold text-slate-800">
                            Portal ATK-RTK
                        </p>
                        <p class="text-[10px] text-slate-500">
                            Inventory System
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-md bg-[#F4B400] text-slate-900">
                        <svg class="h-4.5 w-4.5" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-5M12 3.5l7 3v5.5c0 4.5-2.8 7.6-7 8.5-4.2-.9-7-4-7-8.5V6.5l7-3Z" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            Role Aktif
                        </p>
                        <p class="truncate text-sm font-bold uppercase text-slate-800">
                            {{ $role }}
                        </p>
                        <p class="text-[10px] text-slate-500">
                            Hak akses pengguna
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-md bg-[#1FA8C9] text-white">
                        <svg class="h-4.5 w-4.5" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            User Aktif
                        </p>
                        <p class="truncate text-sm font-bold text-slate-800">
                            {{ $user->name }}
                        </p>
                        <p class="truncate text-[10px] text-slate-500">
                            {{ $user->email }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-md bg-[#E74C3C] text-white">
                        <svg class="h-4.5 w-4.5" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5h16M4 12h16M4 17.5h16" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            Menu Akses
                        </p>
                        <p class="truncate text-sm font-bold text-slate-800">
                            {{ count($quickMenus) }} Menu
                        </p>
                        <p class="text-[10px] text-slate-500">
                            Berdasarkan role akun
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Panel --}}
        <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
            <div class="border-t-4 border-[#1FA8C9]">
                <div class="grid grid-cols-1 lg:grid-cols-3">
                    {{-- Left Content --}}
                    <div class="border-r border-slate-200 p-3 lg:col-span-2">
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-[13px] font-bold text-slate-800">
                                    Menu Cepat
                                </h3>
                                <p class="text-[10px] text-slate-500">
                                    Pilihan menu sesuai role akun.
                                </p>
                            </div>

                            <span class="rounded-full bg-[#08B05A] px-2.5 py-1 text-[10px] font-bold text-white">
                                {{ strtoupper($role) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($quickMenus as $menu)
                                <a href="{{ $menu['route'] }}"
                                   class="group rounded-md border border-slate-200 bg-slate-50 p-3 transition hover:border-[#3B8DBD] hover:bg-white hover:shadow-sm">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-md {{ $menu['color'] }} text-white shadow-sm">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-semibold text-slate-800 group-hover:text-[#2C87B8]">
                                                {{ $menu['title'] }}
                                            </p>
                                            <p class="mt-0.5 line-clamp-1 text-[10px] text-slate-500">
                                                {{ $menu['description'] }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Right Info --}}
                    <div class="bg-slate-50 p-3">
                        <div class="mb-3">
                            <h3 class="text-[13px] font-bold text-slate-800">
                                Informasi Sistem
                            </h3>
                            <p class="text-[10px] text-slate-500">
                                Detail akun dan akses.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <div class="rounded-md border border-slate-200 bg-white p-2.5">
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-semibold text-slate-700">
                                        Status Sistem
                                    </p>
                                    <span class="rounded-full bg-[#08B05A] px-2 py-0.5 text-[10px] font-bold text-white">
                                        Running
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-md border border-slate-200 bg-white p-2.5">
                                <p class="text-[11px] font-semibold text-slate-700">
                                    Aplikasi
                                </p>
                                <p class="mt-0.5 text-xs font-bold text-slate-800">
                                    Portal ATK-RTK
                                </p>
                                <p class="text-[10px] text-slate-500">
                                    Corporate Inventory
                                </p>
                            </div>

                            <div class="rounded-md border border-slate-200 bg-white p-2.5">
                                <p class="text-[11px] font-semibold text-slate-700">
                                    Pengguna
                                </p>
                                <p class="mt-0.5 truncate text-xs font-bold text-slate-800">
                                    {{ $user->name }}
                                </p>
                                <p class="truncate text-[10px] text-slate-500">
                                    {{ $user->email }}
                                </p>
                            </div>

                            <div class="rounded-md border border-slate-200 bg-white p-2.5">
                                <p class="text-[11px] font-semibold text-slate-700">
                                    Tanggal
                                </p>
                                <p class="mt-0.5 text-xs font-bold text-slate-800">
                                    {{ now()->format('d M Y') }}
                                </p>
                                <p class="text-[10px] text-slate-500">
                                    {{ now()->format('H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (in_array($role, ['admin', 'approver']))
            {{-- Monitoring Tables --}}
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                {{-- 5 Barang Stok Terendah --}}
                <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h3 class="text-sm font-bold text-slate-800">
                            5 Barang dengan Stok Terendah
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Prioritas barang yang perlu dipantau.
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
                                        Barang
                                    </th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Kategori
                                    </th>
                                    <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Stok
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($lowStockItems as $item)
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-4 py-3 text-[12px] text-slate-600">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#1FA8C9] text-white">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 8.25-9-5.25-9 5.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21M3 8.25v7.5L12 21" />
                                                    </svg>
                                                </div>

                                                <div>
                                                    <p class="text-xs font-bold text-slate-800">
                                                        {{ $item->name ?? '-' }}
                                                    </p>
                                                    <p class="text-[10px] text-slate-500">
                                                        {{ $item->code ?? '-' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-[12px] text-slate-600">
                                            {{ $item->category->name ?? '-' }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            @php
                                                $currentStock = $item->current_stock ?? 0;
                                                $minimumStock = $item->minimum_stock ?? 0;
                                                $isLow = $minimumStock > 0 && $currentStock <= $minimumStock;
                                            @endphp

                                            <span class="inline-flex min-w-[34px] justify-center rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 ring-inset
                                                {{ $isLow ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">
                                                {{ $currentStock }}
                                            </span>

                                            @if ($minimumStock > 0)
                                                <p class="mt-1 text-[10px] text-slate-400">
                                                    Min: {{ $minimumStock }}
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center">
                                            <p class="text-xs font-semibold text-slate-600">
                                                Belum ada data barang.
                                            </p>
                                            <p class="mt-1 text-[10px] text-slate-400">
                                                Tambahkan data barang terlebih dahulu.
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pengajuan Terbaru --}}
                <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h3 class="text-sm font-bold text-slate-800">
                            Pengajuan Terbaru
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Riwayat pengajuan terakhir.
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
                                        Nomor
                                    </th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        User
                                    </th>
                                    <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Status
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($latestRequests as $request)
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-4 py-3 text-[12px] text-slate-600">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3">
                                            <p class="text-xs font-bold text-slate-800">
                                                {{ $request->request_number ?? '-' }}
                                            </p>
                                            <p class="text-[10px] text-slate-500">
                                                {{ $request->request_date ? $request->request_date->format('d-m-Y') : '-' }}
                                            </p>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-[12px] text-slate-600">
                                            {{ $request->user->name ?? '-' }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-center">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset {{ $statusBadge($request->status ?? null) }}">
                                                {{ $statusLabel($request->status ?? null) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center">
                                            <p class="text-xs font-semibold text-slate-600">
                                                Belum ada data pengajuan.
                                            </p>
                                            <p class="mt-1 text-[10px] text-slate-400">
                                                Pengajuan terbaru akan tampil di sini.
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="rounded-md border border-[#D9E1E7] bg-white px-3 py-2 text-[11px] font-semibold text-slate-600 shadow-sm">
            Portal ATK-RTK © {{ date('Y') }} - Sistem Pengelolaan ATK/RTK Perusahaan
        </div>
    </div>
</x-app-layout>