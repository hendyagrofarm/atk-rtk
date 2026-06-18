<x-guest-layout>
    <div class="border-b border-slate-200 px-5 py-4">
        <h1 class="text-base font-semibold leading-6 text-slate-900">
            Lupa Password
        </h1>
        <p class="text-[11px] leading-4 text-slate-500">
            Masukkan email akun untuk menerima link reset password.
        </p>
    </div>

    <div class="px-5 py-5">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-4 rounded-md border border-indigo-100 bg-indigo-50 px-3 py-2">
            <p class="text-[10px] leading-4 text-indigo-800">
                Jika email terdaftar, sistem akan mengirim link untuk membuat password baru.
            </p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-3">
            @csrf

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
                    autofocus
                    placeholder="Masukkan email"
                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >

                @error('email')
                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-emerald-700 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                Kirim Link Reset Password
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700">
                Kembali ke Login
            </a>
        </div>
    </div>
</x-guest-layout>