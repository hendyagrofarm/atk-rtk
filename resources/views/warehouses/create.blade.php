<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold leading-5 text-white">
                    Tambah Gudang
                </h1>

                <p class="text-[10px] leading-4 text-white/80">
                    Tambahkan lokasi penyimpanan barang baru.
                </p>
            </div>

            <a
                href="{{ route('warehouses.index') }}"
                class="inline-flex items-center gap-1.5 rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-white/20"
            >
                <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                    />
                </svg>

                Kembali
            </a>
        </div>
    </x-slot>

    <div class="space-y-3">
        {{-- Validasi --}}
        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 shadow-sm">
                <div class="flex items-start gap-2">
                    <svg
                        class="mt-0.5 h-4 w-4 shrink-0 text-red-600"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v4m0 4h.01M10.3 3.6 2.7 17a2 2 0 0 0 1.75 3h15.1a2 2 0 0 0 1.75-3L13.7 3.6a2 2 0 0 0-3.4 0Z"
                        />
                    </svg>

                    <div>
                        <p class="text-[11px] font-semibold text-red-800">
                            Data belum dapat disimpan.
                        </p>

                        <ul class="mt-1 list-inside list-disc text-[10px] leading-4 text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form --}}
        <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m21 8.25-9-5.25-9 5.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21M3 8.25v7.5L12 21"
                            />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-sm font-bold text-slate-800">
                            Data Gudang
                        </h2>

                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            Lengkapi informasi gudang. Kolom bertanda bintang wajib diisi.
                        </p>
                    </div>
                </div>
            </div>

            <form
                action="{{ route('warehouses.store') }}"
                method="POST"
            >
                @csrf

                <div class="p-4">
                    @include('warehouses._form')
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <a
                        href="{{ route('warehouses.index') }}"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 5v14m7-7H5"
                            />
                        </svg>

                        Simpan Gudang
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>