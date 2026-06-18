<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Tambah Barang
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Tambahkan data barang ATK/RTK baru ke sistem.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl">
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
                            Form Tambah Barang
                        </h2>
                        <p class="text-[10px] leading-4 text-slate-500">
                            Lengkapi detail barang di bawah ini.
                        </p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('items.store') }}">
                @include('items._form')
            </form>
        </div>
    </div>
</x-app-layout>