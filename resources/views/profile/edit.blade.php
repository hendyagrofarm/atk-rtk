<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-sm font-semibold leading-5 text-slate-800">
                Profile Akun
            </h2>
            <p class="text-[10px] leading-4 text-slate-500">
                Kelola informasi akun dan keamanan password.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-3">
        {{-- Flash Message Profile Updated --}}
        @if (session('status') === 'profile-updated')
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-medium text-emerald-700 shadow-sm">
                Profile berhasil diperbarui.
            </div>
        @endif

        {{-- Flash Message Password Updated --}}
        @if (session('status') === 'password-updated')
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-medium text-emerald-700 shadow-sm">
                Password berhasil diperbarui.
            </div>
        @endif

        {{-- Informasi Profile --}}
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <svg class="h-4.5 w-4.5" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold leading-5 text-slate-800">
                            Informasi Profile
                        </h3>
                        <p class="text-[10px] leading-4 text-slate-500">
                            Perbarui nama dan email akun Anda.
                        </p>
                    </div>
                </div>
            </div>

            <div class="px-4 py-4">
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-3">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Nama <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $user->name) }}"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-emerald-600 focus:ring-emerald-600 @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            required
                            autofocus
                            autocomplete="name"
                        >

                        @error('name')
                            <p class="mt-1 text-[10px] text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Email <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $user->email) }}"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-emerald-600 focus:ring-emerald-600 @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            required
                            autocomplete="username"
                        >

                        @error('email')
                            <p class="mt-1 text-[10px] text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Role
                        </label>

                        <div class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600 ring-1 ring-inset ring-slate-200">
                            {{ $user->role }}
                        </div>

                        <p class="mt-1 text-[10px] leading-4 text-slate-500">
                            Role hanya bisa diubah oleh admin melalui menu Manajemen User.
                        </p>
                    </div>

                    <div class="flex items-center justify-end border-t border-slate-200 pt-3">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10 18.25 19.5 6.75" />
                            </svg>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Update Password --}}
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <svg class="h-4.5 w-4.5" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 0h10.5A1.5 1.5 0 0 1 18.75 12v6.75a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold leading-5 text-slate-800">
                            Update Password
                        </h3>
                        <p class="text-[10px] leading-4 text-slate-500">
                            Gunakan password yang kuat dan aman.
                        </p>
                    </div>
                </div>
            </div>

            <div class="px-4 py-4">
                <form method="POST" action="{{ route('password.update') }}" class="space-y-3">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="update_password_current_password" class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Password Saat Ini <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="update_password_current_password"
                            name="current_password"
                            type="password"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-emerald-600 focus:ring-emerald-600 @error('current_password', 'updatePassword') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            autocomplete="current-password"
                            placeholder="Masukkan password saat ini"
                        >

                        @error('current_password', 'updatePassword')
                            <p class="mt-1 text-[10px] text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="update_password_password" class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Password Baru <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="update_password_password"
                            name="password"
                            type="password"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-emerald-600 focus:ring-emerald-600 @error('password', 'updatePassword') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            autocomplete="new-password"
                            placeholder="Masukkan password baru"
                        >

                        @error('password', 'updatePassword')
                            <p class="mt-1 text-[10px] text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="update_password_password_confirmation" class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Konfirmasi Password Baru <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="update_password_password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-emerald-600 focus:ring-emerald-600 @error('password_confirmation', 'updatePassword') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            autocomplete="new-password"
                            placeholder="Ulangi password baru"
                        >

                        @error('password_confirmation', 'updatePassword')
                            <p class="mt-1 text-[10px] text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end border-t border-slate-200 pt-3">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10 18.25 19.5 6.75" />
                            </svg>
                            Simpan Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>