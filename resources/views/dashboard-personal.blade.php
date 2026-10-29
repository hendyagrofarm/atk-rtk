<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-[13px] font-semibold leading-4 text-white">
                Dashboard Portal ATK-RTK
            </h2>
            <p class="text-[10px] leading-3 text-white/80">
                {{ $isApprover ? 'Ringkasan persetujuan dan pengajuan Anda.' : 'Ringkasan status pengajuan Anda.' }}
            </p>
        </div>
    </x-slot>

    @php
        $hour = (int) now()->timezone(config('app.timezone'))->format('H');
        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        $roleLabel = $user->role === 'approver' ? 'APPROVER' : 'STAFF';
        $positionName = $user->position?->name;

        $statusLabel = fn ($status) => match ($status) {
            'pending' => 'Diproses',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => ucfirst((string) ($status ?: '-')),
        };

        $statusClass = fn ($status) => match ($status) {
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'rejected' => 'bg-red-50 text-red-700 ring-red-200',
            default => 'bg-slate-50 text-slate-700 ring-slate-200',
        };

        $itemSummary = function ($request) {
            $first = $request->details->first();
            if (! $first) {
                return '-';
            }

            $name = $first->custom_item_name ?: ($first->item?->name ?? '-');
            $more = $request->details->count() - 1;

            return $more > 0 ? $name . ' +' . $more . ' barang lain' : $name;
        };

        $quickMenus = [];
        if ($user->role === 'staff') {
            $quickMenus = [
                ['title' => 'Pengajuan dari Stok', 'description' => 'Ajukan barang yang tersedia.', 'route' => route('staff.requests.create'), 'color' => 'bg-[#08B05A]'],
                ['title' => 'Pengajuan Baru / Custom', 'description' => 'Ajukan barang yang belum tersedia.', 'route' => route('staff.requests.custom.create'), 'color' => 'bg-[#F4B400]'],
                ['title' => 'Pengajuan Saya', 'description' => 'Lacak seluruh riwayat pengajuan.', 'route' => route('staff.requests.index'), 'color' => 'bg-[#1FA8C9]'],
            ];
        }
        if ($isApprover) {
            $quickMenus[] = ['title' => 'Approval Pengajuan', 'description' => 'Proses pengajuan yang menunggu Anda.', 'route' => route('approvals.index'), 'color' => 'bg-[#E74C3C]'];
        }
    @endphp

    <div class="space-y-3">
        {{-- Sapaan --}}
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-[#D9E1E7] bg-white p-4 shadow-sm">
            <div>
                <p class="text-[11px] text-slate-500">{{ $greeting }},</p>
                <h3 class="text-base font-bold text-slate-800">{{ $user->name }}</h3>
                <p class="text-[11px] text-slate-500">
                    {{ $positionName ?: 'Jabatan belum diatur' }}
                    @if ($user->entity?->name)
                        &middot; {{ $user->entity->name }}
                    @endif
                </p>
            </div>

            <span class="rounded-full bg-[#08B05A] px-2.5 py-1 text-[10px] font-bold text-white">{{ $roleLabel }}</span>
        </div>


        {{-- Perlu tindakan --}}
        @if ($isApprover)
            <div class="rounded-md border {{ $awaiting->isNotEmpty() ? 'border-amber-300 bg-amber-50' : 'border-[#D9E1E7] bg-white' }} p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-[10px] font-semibold uppercase text-slate-500">Menunggu persetujuan Anda</p>
                        <p class="mt-1 text-2xl font-bold {{ $awaiting->isNotEmpty() ? 'text-amber-700' : 'text-slate-700' }}">
                            {{ number_format($awaiting->count()) }}
                        </p>
                    </div>

                    <a href="{{ route('approvals.index') }}"
                       class="rounded-md bg-[#2C87B8] px-3 py-2 text-[11px] font-bold text-white hover:bg-[#256f96]">
                        Buka Approval
                    </a>
                </div>
            </div>
        @endif

        {{-- Statistik --}}
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @if ($isApprover)
                <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase text-slate-500">Disetujui Bulan Ini</p>
                    <p class="mt-1 text-lg font-bold text-emerald-700">{{ number_format($processed['approved']) }}</p>
                    <p class="text-[10px] text-slate-400">oleh Anda</p>
                </div>

                <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase text-slate-500">Ditolak Bulan Ini</p>
                    <p class="mt-1 text-lg font-bold text-red-700">{{ number_format($processed['rejected']) }}</p>
                    <p class="text-[10px] text-slate-400">oleh Anda</p>
                </div>
            @endif

            @if ($user->role === 'staff')
                <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase text-slate-500">Total Pengajuan Saya</p>
                    <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($ownCounts['total']) }}</p>
                </div>

                <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase text-slate-500">Sedang Diproses</p>
                    <p class="mt-1 text-lg font-bold text-amber-700">{{ number_format($ownCounts['pending']) }}</p>
                </div>

                <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase text-slate-500">Disetujui</p>
                    <p class="mt-1 text-lg font-bold text-emerald-700">{{ number_format($ownCounts['approved']) }}</p>
                </div>

                <div class="rounded-md border border-[#D9E1E7] bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase text-slate-500">Ditolak</p>
                    <p class="mt-1 text-lg font-bold text-red-700">{{ number_format($ownCounts['rejected']) }}</p>
                </div>
            @endif
        </div>

        {{-- Menu cepat --}}
        <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
            <div class="border-t-4 border-[#1FA8C9] p-3">
                <h3 class="text-[13px] font-bold text-slate-800">Menu Cepat</h3>
                <p class="mb-3 text-[10px] text-slate-500">Pilihan menu sesuai peran akun.</p>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($quickMenus as $menu)
                        <a href="{{ $menu['route'] }}"
                           class="group rounded-md border border-slate-200 bg-slate-50 p-3 transition hover:border-[#3B8DBD] hover:bg-white hover:shadow-sm">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md {{ $menu['color'] }} text-white shadow-sm">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-xs font-semibold text-slate-800 group-hover:text-[#2C87B8]">{{ $menu['title'] }}</p>
                                    <p class="mt-0.5 line-clamp-1 text-[10px] text-slate-500">{{ $menu['description'] }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
            {{-- Daftar menunggu persetujuan --}}
            @if ($isApprover)
                <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h3 class="text-sm font-bold text-slate-800">Perlu Persetujuan Anda</h3>
                        <p class="text-[11px] text-slate-500">Pengajuan yang menunggu tindakan Anda, terlama lebih dulu.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($awaiting->take(5) as $request)
                            <a href="{{ route('approvals.show', $request) }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800">{{ $request->request_number }}</p>
                                    <p class="truncate text-[11px] text-slate-600">{{ $request->pic_name ?: $request->user?->name }} &middot; {{ $request->department ?: ($request->entity?->code ?? '-') }}</p>
                                    <p class="text-[10px] text-slate-400">Diajukan {{ $request->created_at?->diffForHumans() }}</p>
                                </div>

                                <span class="shrink-0 rounded-md bg-[#2C87B8] px-2.5 py-1.5 text-[10px] font-bold text-white">Proses</span>
                            </a>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <p class="text-xs font-semibold text-slate-600">Tidak ada pengajuan yang menunggu.</p>
                                <p class="mt-1 text-[10px] text-slate-400">Semua pengajuan sudah Anda proses.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Pengajuan saya --}}
            @if ($user->role === 'staff' || $myRequests->isNotEmpty())
                <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Pengajuan Saya</h3>
                            <p class="text-[11px] text-slate-500">Status terkini lima pengajuan terakhir.</p>
                        </div>

                        @if ($user->role === 'staff')
                            <a href="{{ route('staff.requests.index') }}" class="text-[11px] font-semibold text-[#2C87B8] hover:underline">Lihat semua</a>
                        @endif
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($myRequests as $request)
                            @php $progress = \App\Support\RequestTimeline::summary($request); @endphp

                            <a href="{{ $user->role === 'staff' ? route('staff.requests.show', $request->id) : '#' }}" class="block px-4 py-3 hover:bg-slate-50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800">{{ $request->request_number }}</p>
                                        <p class="truncate text-[11px] text-slate-600">{{ $itemSummary($request) }}</p>
                                    </div>

                                    <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset {{ $statusClass($request->status) }}">
                                        {{ $statusLabel($request->status) }}
                                    </span>
                                </div>

                                <p class="mt-1 text-[10px] text-slate-500">
                                    {{ $progress['label'] }}
                                    @if ($progress['time'])
                                        <span class="text-slate-400">&middot; {{ $progress['time'] }}</span>
                                    @endif
                                </p>
                            </a>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <p class="text-xs font-semibold text-slate-600">Belum ada pengajuan.</p>
                                <p class="mt-1 text-[10px] text-slate-400">Buat pengajuan dari menu cepat di atas.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Riwayat yang sudah diproses (approver) --}}
            @if ($isApprover)
                <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h3 class="text-sm font-bold text-slate-800">Riwayat Terakhir</h3>
                        <p class="text-[11px] text-slate-500">Pengajuan yang sudah selesai diproses.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($recentProcessed as $request)
                            <a href="{{ route('approvals.show', $request) }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800">{{ $request->request_number }}</p>
                                    <p class="truncate text-[11px] text-slate-600">{{ $request->pic_name ?: $request->user?->name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ \App\Support\RequestTimeline::formatTime($request->approved_at ?? $request->updated_at) }}</p>
                                </div>

                                <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset {{ $statusClass($request->status) }}">
                                    {{ $statusLabel($request->status) }}
                                </span>
                            </a>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <p class="text-xs font-semibold text-slate-600">Belum ada riwayat.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        <div class="rounded-md border border-[#D9E1E7] bg-white px-3 py-2 text-[11px] font-semibold text-slate-600 shadow-sm">
            Portal ATK-RTK &copy; {{ date('Y') }} - Sistem Pengelolaan ATK/RTK Perusahaan
        </div>
    </div>
</x-app-layout>
