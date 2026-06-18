<section>
    <header class="mb-3">
        <h2 class="text-xs font-semibold leading-5 text-slate-800">
            Data Profile
        </h2>

        <p class="text-[10px] leading-4 text-slate-500">
            Perbarui informasi nama dan email akun kamu.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-3">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Nama
                <span class="text-red-500">*</span>
            </label>

            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $user->name) }}"
                required
                autofocus
                autocomplete="name"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                placeholder="Masukkan nama"
            >

            @error('name')
                <p class="mt-1 text-[10px] text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Email
                <span class="text-red-500">*</span>
            </label>

            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                placeholder="Masukkan email"
            >

            @error('email')
                <p class="mt-1 text-[10px] text-red-600">
                    {{ $message }}
                </p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                    <p class="text-[10px] leading-4 text-amber-800">
                        Email kamu belum terverifikasi.

                        <button
                            form="send-verification"
                            class="font-semibold underline hover:text-amber-900"
                        >
                            Kirim ulang email verifikasi.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 text-[10px] font-semibold text-emerald-700">
                            Link verifikasi baru sudah dikirim ke email kamu.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button
                type="submit"
                class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5 9.5 17 19 7" />
                </svg>
                Simpan
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-[11px] font-medium text-emerald-600"
                >
                    Tersimpan.
                </p>
            @endif
        </div>
    </form>
</section>