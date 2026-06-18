<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Edit Barang
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Perbarui data barang ATK/RTK yang sudah terdaftar.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl">
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
                            Form Edit Barang
                        </h2>
                        <p class="text-[10px] leading-4 text-slate-500">
                            Mengubah data barang: {{ $item->name }}
                        </p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('items.update', $item) }}">
                @method('PUT')

                @include('items._form')
            </form>
        </div>
    </div>
</x-app-layout>