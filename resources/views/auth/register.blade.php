<x-guest-layout>
    <div class="border-b border-slate-200 px-5 py-4">
        <h1 class="text-base font-semibold leading-6 text-slate-900">
            Register Akun
        </h1>
        <p class="text-[11px] leading-4 text-slate-500">
            Buat akun baru untuk mengakses Sistem ATK/RTK.
        </p>
    </div>

    <div class="px-5 py-5">
        <form method="POST" action="{{ route('register') }}" class="space-y-3">
            @csrf

            <div>
                <label for="name" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                    Nama
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Masukkan nama lengkap"
                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >

                @error('name')
                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="username"
                    placeholder="Masukkan email"
                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >

                @error('email')
                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Masukkan password"
                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >

                @error('password')
                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                    Konfirmasi Password
                </label>

                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi password"
                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password_confirmation') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >

                @error('password_confirmation')
                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-emerald-700 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                Register
            </button>
        </form>

        <div class="mt-4 rounded-md bg-slate-50 px-3 py-2 text-center text-[11px] text-slate-600 ring-1 ring-slate-200">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">
                Login
            </a>
        </div>
    </div>
</x-guest-layout>