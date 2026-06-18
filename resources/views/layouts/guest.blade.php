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

<body class="min-h-screen bg-slate-100 font-sans antialiased text-slate-800">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            {{-- Brand --}}
            <div class="mb-5 flex justify-center">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <img
        src="{{ asset('images/logo.png') }}"
        alt="Logo Portal ATK-RTK"
        class="h-9 w-9 object-contain"
    >
</div>

                    <div>
                        <p class="text-base font-bold leading-5 text-slate-900">
                            Portal ATK-RTK
                        </p>
                        <p class="text-xs leading-4 text-slate-500">
                            Sistem Pengelolaan ATK/RTK
                        </p>
                    </div>
                </a>
            </div>

            {{-- Auth Card --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {{ $slot }}
            </div>

            <p class="mt-5 text-center text-xs text-slate-500">
                © {{ date('Y') }} Portal ATK-RTK.
            </p>
        </div>
    </div>
</body>
</html>