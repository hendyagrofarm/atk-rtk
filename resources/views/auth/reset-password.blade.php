<x-guest-layout>
    <div class="border-b border-slate-200 px-5 py-4">
        <h1 class="text-base font-semibold leading-6 text-slate-900">
            Reset Password
        </h1>
        <p class="text-[11px] leading-4 text-slate-500">
            Buat password baru untuk akun kamu.
        </p>
    </div>

    <div class="px-5 py-5">
        <form method="POST" action="{{ route('password.store') }}" class="space-y-3">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $request->email) }}"
                    required
                    autofocus
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
                    Password Baru
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Masukkan password baru"
                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >

                @error('password')
                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                    Konfirmasi Password Baru
                </label>

                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi password baru"
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
                Simpan Password Baru
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700">
                Kembali ke Login
            </a>
        </div>
    </div>
</x-guest-layout>