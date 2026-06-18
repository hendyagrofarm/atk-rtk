<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Tambah Kategori Barang
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Tambahkan kategori baru untuk pengelompokan barang ATK/RTK.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-indigo-100 text-indigo-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-xs font-semibold leading-5 text-slate-800">
                            Form Tambah Kategori
                        </h2>
                        <p class="text-[10px] leading-4 text-slate-500">
                            Lengkapi data kategori di bawah ini.
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('categories.store') }}" method="POST">
                @csrf

                <div class="space-y-3 px-4 py-4">
                    @include('categories._form')
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <a href="{{ route('categories.index') }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19 3 12m0 0 7-7M3 12h18" />
                        </svg>
                        Batal
                    </a>

                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-indigo-700">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5 9.5 17 19 7" />
                        </svg>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>