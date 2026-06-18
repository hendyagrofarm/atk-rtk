<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-800">
                    Edit User
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Ubah data user, role, status, atau password.
                </p>
            </div>

            <a href="{{ route('users.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="mb-1 block text-sm font-semibold text-slate-700">
                            Nama User <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $user->name) }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $user->email) }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="mb-1 block text-sm font-semibold text-slate-700">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="role"
                            name="role"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @disabled($user->id === auth()->id())
                        >
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>
                                    {{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>

                        @if ($user->id === auth()->id())
                            <input type="hidden" name="role" value="{{ $user->role }}">
                            <p class="mt-1 text-xs text-slate-500">
                                Role akun sendiri tidak bisa diubah agar admin tidak kehilangan akses.
                            </p>
                        @endif

                        @error('role')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-800">
                            Ubah Password
                        </p>
                        <p class="mt-1 text-xs text-amber-700">
                            Kosongkan password jika tidak ingin mengganti password user.
                        </p>

                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="password" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Password Baru
                                </label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Kosongkan jika tidak diganti"
                                >
                                @error('password')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Konfirmasi Password Baru
                                </label>
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Ulangi password baru"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <label class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                @checked(old('is_active', $user->is_active))
                                @disabled($user->id === auth()->id())
                            >
                            <span>
                                <span class="block text-sm font-semibold text-slate-700">
                                    User aktif
                                </span>
                                <span class="block text-xs text-slate-500">
                                    Jika dinonaktifkan, user tidak dapat login ke aplikasi.
                                </span>
                            </span>
                        </label>

                        @if ($user->id === auth()->id())
                            <input type="hidden" name="is_active" value="1">
                            <p class="mt-2 text-xs text-slate-500">
                                Akun sendiri tidak bisa dinonaktifkan.
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                        <a href="{{ route('users.index') }}"
                           class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10 18.25 19.5 6.75" />
                            </svg>
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>