<x-guest-layout>
    <div class="border-b border-slate-200 px-6 py-5">
        <h1 class="text-lg font-bold leading-6 text-slate-900">
            Login Sistem
        </h1>
        <p class="mt-1 text-sm leading-5 text-slate-500">
            Masuk ke Portal ATK-RTK.
        </p>
    </div>

    <div class="px-6 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Masukkan email"
                    class="block w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600 @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >

                @error('email')
                    <p class="mt-1 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="block text-sm font-semibold text-slate-700">
                        Password
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                            Lupa password?
                        </a>
                    @endif
                </div>

                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan password"
                    class="block w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600 @error('password') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >

                @error('password')
                    <p class="mt-1 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Remember --}}
            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center gap-2">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="rounded border-slate-300 text-emerald-700 shadow-sm focus:ring-emerald-600"
                    >

                    <span class="text-sm text-slate-600">
                        Ingat saya
                    </span>
                </label>
            </div>

            {{-- Button --}}
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800"
            >
                Login
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </button>
        </form>

        <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
            <p class="text-center text-xs leading-5 text-slate-500">
                Sistem ini hanya digunakan untuk kebutuhan internal perusahaan.
            </p>
        </div>
    </div>
</x-guest-layout>