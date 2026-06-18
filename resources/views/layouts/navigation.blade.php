@php
    $userRole = Auth::user()->role ?? null;

    $navItemClass = function ($active) {
        return $active
            ? 'group flex items-center gap-2 rounded-md border-l-4 border-[#31B7E9] bg-[#2C87B8] px-2.5 py-2 text-[12px] font-semibold text-white shadow-sm'
            : 'group flex items-center gap-2 rounded-md border-l-4 border-transparent px-2.5 py-2 text-[12px] font-medium text-slate-300 transition hover:bg-[#243746] hover:text-white';
    };
@endphp

{{-- Mobile Overlay --}}
<div
    x-show="sidebarOpen"
    x-transition.opacity
    class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
    @click="sidebarOpen = false"
    style="display: none;"
></div>

{{-- Sidebar --}}
<aside
    class="fixed inset-y-0 left-0 z-50 w-52 transform bg-[#1F2D3A] text-slate-200 shadow-xl transition-transform duration-300 lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
>
    <div class="flex h-full flex-col">
        {{-- Brand --}}
        <div class="flex h-14 items-center justify-between border-b border-white/10 px-3">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-white/20">
    <img
        src="{{ asset('images/logo2.png') }}"
        alt="Logo Portal ATK-RTK"
        class="h-8 w-8 object-contain"
    >
</div>

                <div class="min-w-0">
                    <p class="truncate text-[13px] font-bold leading-4 text-white">
                        Portal ATK-RTK
                    </p>
                    <p class="truncate text-[10px] leading-3 text-slate-400">
                        Inventory System
                    </p>
                </div>
            </a>

            <button
                type="button"
                class="rounded-md p-1.5 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden"
                @click="sidebarOpen = false"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Menu --}}
        <nav class="sidebar-scroll flex-1 space-y-3 overflow-y-auto px-2 py-3">
            <div>
                <p class="mb-1 px-2.5 text-[9px] font-bold uppercase tracking-wider text-slate-500">
                    Menu Utama
                </p>

                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="{{ $navItemClass(request()->routeIs('dashboard')) }}">
                        <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5 12 4l9 9.5M5 11.5V20h14v-8.5" />
                        </svg>
                        <span class="truncate">Dashboard</span>
                    </a>
                </div>
            </div>

            @auth
                @if ($userRole === 'admin')
                    <div>
                        <p class="mb-1 px-2.5 text-[9px] font-bold uppercase tracking-wider text-slate-500">
                            Administrasi
                        </p>

                        <div class="space-y-1">
                            <a href="{{ route('users.index') }}"
                               class="{{ $navItemClass(request()->routeIs('users.*')) }}">
                                <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0M18 8.25v4.5m2.25-2.25h-4.5" />
                                </svg>
                                <span class="truncate">Manajemen User</span>
                            </a>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1 px-2.5 text-[9px] font-bold uppercase tracking-wider text-slate-500">
                            Master Data
                        </p>

                        <div class="space-y-1">
                            <a href="{{ route('categories.index') }}"
                               class="{{ $navItemClass(request()->routeIs('categories.*')) }}">
                                <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h2A2.5 2.5 0 0 1 11 6.5v2A2.5 2.5 0 0 1 8.5 11h-2A2.5 2.5 0 0 1 4 8.5v-2ZM13 6.5A2.5 2.5 0 0 1 15.5 4h2A2.5 2.5 0 0 1 20 6.5v2a2.5 2.5 0 0 1-2.5 2.5h-2A2.5 2.5 0 0 1 13 8.5v-2ZM4 15.5A2.5 2.5 0 0 1 6.5 13h2a2.5 2.5 0 0 1 2.5 2.5v2A2.5 2.5 0 0 1 8.5 20h-2A2.5 2.5 0 0 1 4 17.5v-2ZM13 15.5a2.5 2.5 0 0 1 2.5-2.5h2a2.5 2.5 0 0 1 2.5 2.5v2a2.5 2.5 0 0 1-2.5 2.5h-2a2.5 2.5 0 0 1-2.5-2.5v-2Z" />
                                </svg>
                                <span class="truncate">Kategori</span>
                            </a>

                            <a href="{{ route('items.index') }}"
                               class="{{ $navItemClass(request()->routeIs('items.*')) }}">
                                <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 8.25-9-5.25-9 5.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21M3 8.25v7.5L12 21" />
                                </svg>
                                <span class="truncate">Barang</span>
                            </a>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1 px-2.5 text-[9px] font-bold uppercase tracking-wider text-slate-500">
                            Transaksi
                        </p>

                        <div class="space-y-1">
                            <a href="{{ route('stock-ins.index') }}"
                               class="{{ $navItemClass(request()->routeIs('stock-ins.*')) }}">
                                <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0 4-4m-4 4-4-4M4 16.5V18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1.5" />
                                </svg>
                                <span class="truncate">Stok Masuk</span>
                            </a>

                            <a href="{{ route('reports.dashboard') }}"
                               class="{{ $navItemClass(request()->routeIs('reports.*')) }}">
                                <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V4.5M8 19.5v-7M12 19.5v-11M16 19.5v-4M20 19.5v-14" />
                                </svg>
                                <span class="truncate">Laporan</span>
                            </a>
                        </div>
                    </div>
                @endif

                @if ($userRole === 'staff')
                    <div>
                        <p class="mb-1 px-2.5 text-[9px] font-bold uppercase tracking-wider text-slate-500">
                            Pengajuan
                        </p>

                        <div class="space-y-1">
                            <a href="{{ route('staff.requests.index') }}"
                               class="{{ $navItemClass(request()->routeIs('staff.requests.*')) }}">
                                <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h8M8 15h5M6 3.5h12A1.5 1.5 0 0 1 19.5 5v14A1.5 1.5 0 0 1 18 20.5H6A1.5 1.5 0 0 1 4.5 19V5A1.5 1.5 0 0 1 6 3.5Z" />
                                </svg>
                                <span class="truncate">Pengajuan ATK/RTK</span>
                            </a>
                        </div>
                    </div>
                @endif

                @if (in_array($userRole, ['admin', 'approver']))
                    <div>
                        <p class="mb-1 px-2.5 text-[9px] font-bold uppercase tracking-wider text-slate-500">
                            Approval
                        </p>

                        <div class="space-y-1">
                            <a href="{{ route('approvals.index') }}"
                               class="{{ $navItemClass(request()->routeIs('approvals.*')) }}">
                                <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-5M12 3.5l7 3v5.5c0 4.5-2.8 7.6-7 8.5-4.2-.9-7-4-7-8.5V6.5l7-3Z" />
                                </svg>
                                <span class="truncate">Approval Pengajuan</span>
                            </a>
                        </div>
                    </div>
                @endif
            @endauth
        </nav>

        {{-- Footer --}}
        @auth
            <div class="border-t border-white/10 bg-[#1A2632] p-2">
                <div class="space-y-1">
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-2 rounded-md px-2.5 py-2 text-[12px] font-medium text-slate-300 transition hover:bg-[#243746] hover:text-white {{ request()->routeIs('profile.edit') ? 'bg-[#2C87B8] text-white' : '' }}">
                        <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
                        </svg>
                        <span class="truncate">Profile</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-[12px] font-medium text-red-300 transition hover:bg-red-500/10 hover:text-red-200"
                        >
                            <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 12h9m0 0-3-3m3 3-3 3" />
                            </svg>
                            <span class="truncate">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</aside>