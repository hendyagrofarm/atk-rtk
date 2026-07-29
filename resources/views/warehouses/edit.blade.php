<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold leading-5 text-white">
                    Edit Gudang
                </h1>

                <p class="text-[10px] leading-4 text-white/80">
                    Perbarui informasi {{ $warehouse->name }}.
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
                            Perubahan belum dapat disimpan.
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

        {{-- Informasi singkat --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-md border border-slate-200 bg-white px-3 py-2 shadow-sm">
                <p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">
                    Kode Gudang
                </p>

                <p class="mt-1 text-xs font-bold text-slate-800">
                    {{ $warehouse->code }}
                </p>
            </div>

            <div class="rounded-md border border-slate-200 bg-white px-3 py-2 shadow-sm">
                <p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">
                    Status
                </p>

                <p class="mt-1">
                    @if ($warehouse->is_active)
                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                            Aktif
                        </span>
                    @else
                        <span class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                            Nonaktif
                        </span>
                    @endif
                </p>
            </div>

            <div class="rounded-md border border-slate-200 bg-white px-3 py-2 shadow-sm">
                <p class="text-[9px] font-bold uppercase tracking-wide text-slate-500">
                    Terakhir Diperbarui
                </p>

                <p class="mt-1 text-xs font-semibold text-slate-800">
                    {{ $warehouse->updated_at?->format('d M Y H:i') ?? '-' }}
                </p>
            </div>
        </div>

        {{-- Form --}}
        <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-sky-100 text-sky-700">
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
                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L18.55 2.799M16.862 4.487 19.5 7.125"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 13.5V19A2.5 2.5 0 0 1 17 21.5H5A2.5 2.5 0 0 1 2.5 19V7A2.5 2.5 0 0 1 5 4.5h5.5"
                            />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-sm font-bold text-slate-800">
                            Edit Data Gudang
                        </h2>

                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            Ubah informasi yang diperlukan lalu simpan perubahan.
                        </p>
                    </div>
                </div>
            </div>

            <form
                action="{{ route('warehouses.update', $warehouse) }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div class="p-4">
                    @include('warehouses._form', [
                        'warehouse' => $warehouse,
                    ])
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
                        class="inline-flex items-center justify-center gap-1.5 rounded-md bg-sky-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-sky-700"
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
                                d="M5 12.75 10 17.75 19 8.75"
                            />
                        </svg>

                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>