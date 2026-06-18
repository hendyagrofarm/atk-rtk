<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Edit Kategori Barang
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Perbarui informasi kategori barang ATK/RTK.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.5 18.152 4 19.5l1.348-4.5L16.862 4.487Z" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-xs font-semibold leading-5 text-slate-800">
                            Form Edit Kategori
                        </h2>
                        <p class="text-[10px] leading-4 text-slate-500">
                            Mengubah data kategori: {{ $category->name }}
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-3 px-4 py-4">
                    @include('categories._form', ['category' => $category])
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
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>