<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Portal ATK-RTK') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#EEF2F5] font-sans antialiased text-slate-800">
    <div
        x-data="{ sidebarOpen: false }"
        class="min-h-screen bg-[#EEF2F5]"
    >
        @include('layouts.navigation')

        <div class="lg:pl-52">
            {{-- Topbar --}}
            <header class="app-topbar sticky top-0 z-30 border-b border-[#2D7FA8] bg-[#3B8DBD] text-white shadow-sm">
                <div class="flex h-11 items-center justify-between px-4">
                    <div class="flex min-w-0 items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md p-1.5 text-white/90 hover:bg-white/10 lg:hidden"
                            @click="sidebarOpen = true"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div class="min-w-0">
                            @isset($header)
                                {{ $header }}
                            @else
                                <h1 class="truncate text-[13px] font-semibold leading-4 text-white">
                                    Portal ATK-RTK
                                </h1>
                                <p class="truncate text-[10px] leading-3 text-white/80">
                                    Inventory System
                                </p>
                            @endisset
                        </div>
                    </div>

                    @auth
                        <div class="hidden shrink-0 items-center gap-2 sm:flex">
                            <div class="text-right">
                                <p class="text-[11px] font-semibold leading-4 text-white">
                                    {{ Auth::user()->name }}
                                </p>
                                <p class="text-[10px] leading-3 text-white/80">
                                    {{ Auth::user()->email }}
                                </p>
                            </div>

                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[11px] font-bold text-[#2C87B8]">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            {{-- Page Content --}}
            <main class="px-4 py-3">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>