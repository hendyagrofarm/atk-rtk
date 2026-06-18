<section>
    <header class="mb-3">
        <h2 class="text-xs font-semibold leading-5 text-slate-800">
            Keamanan Password
        </h2>

        <p class="text-[10px] leading-4 text-slate-500">
            Pastikan akun menggunakan password yang panjang dan sulit ditebak.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-3">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Password Saat Ini
                <span class="text-red-500">*</span>
            </label>

            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                autocomplete="current-password"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @if($errors->updatePassword->get('current_password')) border-red-300 focus:border-red-500 focus:ring-red-500 @endif"
                placeholder="Masukkan password saat ini"
            >

            @if($errors->updatePassword->get('current_password'))
                <p class="mt-1 text-[10px] text-red-600">
                    {{ $errors->updatePassword->first('current_password') }}
                </p>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Password Baru
                <span class="text-red-500">*</span>
            </label>

            <input
                id="update_password_password"
                name="password"
                type="password"
                autocomplete="new-password"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @if($errors->updatePassword->get('password')) border-red-300 focus:border-red-500 focus:ring-red-500 @endif"
                placeholder="Masukkan password baru"
            >

            @if($errors->updatePassword->get('password'))
                <p class="mt-1 text-[10px] text-red-600">
                    {{ $errors->updatePassword->first('password') }}
                </p>
            @endif
        </div>

        <div>
            <label for="update_password_password_confirmation" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Konfirmasi Password Baru
                <span class="text-red-500">*</span>
            </label>

            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @if($errors->updatePassword->get('password_confirmation')) border-red-300 focus:border-red-500 focus:ring-red-500 @endif"
                placeholder="Ulangi password baru"
            >

            @if($errors->updatePassword->get('password_confirmation'))
                <p class="mt-1 text-[10px] text-red-600">
                    {{ $errors->updatePassword->first('password_confirmation') }}
                </p>
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
                Simpan Password
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-[11px] font-medium text-emerald-600"
                >
                    Password tersimpan.
                </p>
            @endif
        </div>
    </form>
</section>