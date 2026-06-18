<section>
    <header class="mb-3">
        <h2 class="text-xs font-semibold leading-5 text-red-800">
            Hapus Akun
        </h2>

        <p class="text-[10px] leading-4 text-red-600">
            Setelah akun dihapus, seluruh data dan resource akun akan dihapus secara permanen.
        </p>
    </header>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="inline-flex items-center justify-center gap-1.5 rounded-md bg-red-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-red-700"
    >
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9M19 6h-4.5l-.9-1.5h-3.2L9.5 6H5m2 0v13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V6" />
        </svg>
        Hapus Akun
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-5">
            @csrf
            @method('delete')

            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 flex-none items-center justify-center rounded-md bg-red-100 text-red-700">
                    <svg class="h-4.5 w-4.5" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    </svg>
                </div>

                <div>
                    <h2 class="text-sm font-semibold leading-5 text-slate-900">
                        Yakin ingin menghapus akun?
                    </h2>

                    <p class="mt-1 text-[11px] leading-4 text-slate-600">
                        Setelah akun dihapus, seluruh data dan resource akun akan dihapus secara permanen.
                        Masukkan password untuk konfirmasi penghapusan akun.
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <label for="password" class="sr-only">
                    Password
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-red-500 focus:ring-red-500 @if($errors->userDeletion->get('password')) border-red-300 @endif"
                    placeholder="Masukkan password"
                >

                @if($errors->userDeletion->get('password'))
                    <p class="mt-1 text-[10px] text-red-600">
                        {{ $errors->userDeletion->first('password') }}
                    </p>
                @endif
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-red-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-red-700"
                >
                    Hapus Akun
                </button>
            </div>
        </form>
    </x-modal>
</section>