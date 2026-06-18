<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Portal ATK-RTK</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-screen">
        {{-- Header --}}
        <header class="border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <img
        src="{{ asset('images/logo.png') }}"
        alt="Logo Portal ATK-RTK"
        class="h-9 w-9 object-contain"
    >
</div>

                    <div>
                        <h1 class="text-lg font-bold leading-5 text-slate-900">
                            Portal ATK-RTK
                        </h1>
                        <p class="text-xs text-slate-500">
                            Sistem Pengelolaan ATK/RTK Perusahaan
                        </p>
                    </div>
                </div>

                <div>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                                Login
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        {{-- Main --}}
        <main class="mx-auto max-w-6xl px-6 py-12 lg:py-16">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                {{-- Left Content --}}
                <div>
                    <div class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                        Sistem Internal Perusahaan
                    </div>

                    <h2 class="mt-4 text-3xl font-bold leading-tight text-slate-900 sm:text-4xl">
                        Sistem Pengelolaan
                        <span class="text-emerald-700">ATK/RTK</span>
                        yang lebih rapi dan efisien
                    </h2>

                    <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">
                        Portal ini membantu perusahaan dalam mengelola data barang, stok masuk,
                        pengajuan kebutuhan staff, proses approval, dan laporan secara lebih
                        terstruktur dalam satu sistem.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ route('dashboard') }}"
                                   class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                                    Masuk ke Dashboard
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                                    Login ke Sistem
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            @endauth
                        @endif

                        <a href="#fitur"
                           class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Lihat Fitur
                        </a>
                    </div>


                </div>

                {{-- Right Card --}}
                <div id="fitur">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5">
                            <h3 class="text-lg font-semibold text-slate-900">
                                Fitur Utama
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Ringkasan modul utama yang tersedia di dalam sistem.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h2A2.5 2.5 0 0 1 11 6.5v2A2.5 2.5 0 0 1 8.5 11h-2A2.5 2.5 0 0 1 4 8.5v-2ZM13 6.5A2.5 2.5 0 0 1 15.5 4h2A2.5 2.5 0 0 1 20 6.5v2a2.5 2.5 0 0 1-2.5 2.5h-2A2.5 2.5 0 0 1 13 8.5v-2ZM4 15.5A2.5 2.5 0 0 1 6.5 13h2a2.5 2.5 0 0 1 2.5 2.5v2A2.5 2.5 0 0 1 8.5 20h-2A2.5 2.5 0 0 1 4 17.5v-2ZM13 15.5a2.5 2.5 0 0 1 2.5-2.5h2a2.5 2.5 0 0 1 2.5 2.5v2a2.5 2.5 0 0 1-2.5 2.5h-2a2.5 2.5 0 0 1-2.5-2.5v-2Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">Master Data</h4>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Kelola kategori barang dan data item ATK/RTK.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0 4-4m-4 4-4-4M4 16.5V18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1.5" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">Stok Masuk</h4>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Catat penambahan stok barang dengan lebih terkontrol.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h8M8 15h5M6 3.5h12A1.5 1.5 0 0 1 19.5 5v14A1.5 1.5 0 0 1 18 20.5H6A1.5 1.5 0 0 1 4.5 19V5A1.5 1.5 0 0 1 6 3.5Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">Pengajuan & Approval</h4>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Staff mengajukan kebutuhan, admin/approver memproses approval.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V4.5M8 19.5v-7M12 19.5v-11M16 19.5v-4M20 19.5v-14" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">Laporan</h4>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Pantau rekap stok, pengajuan, dan aktivitas barang.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-6 py-4 text-center text-sm text-slate-500">
                © {{ date('Y') }} Portal ATK-RTK. All rights reserved.
            </div>
        </footer>
    </div>
</body>
</html>