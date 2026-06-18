<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Tambah Stok Masuk
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Catat penambahan stok barang ATK/RTK berdasarkan entitas.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="space-y-3">
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-red-800 shadow-sm">
                    <div class="flex gap-2">
                        <svg class="mt-0.5 h-4 w-4 flex-none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                        </svg>

                        <div>
                            <p class="text-xs font-semibold leading-4">
                                Terjadi Kesalahan
                            </p>
                            <p class="text-[11px] leading-4">
                                {{ session('error') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-indigo-100 text-indigo-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0 4-4m-4 4-4-4M4 16.5V18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1.5" />
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-xs font-semibold leading-5 text-slate-800">
                                Form Tambah Stok Masuk
                            </h2>
                            <p class="text-[10px] leading-4 text-slate-500">
                                Pilih entitas ANR/MAA agar stok bertambah sesuai perusahaan.
                            </p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('stock-ins.store') }}">
                    @csrf

                    <div class="space-y-3 px-4 py-4">
                        <div>
                            <label for="entity_id" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                Entitas
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                name="entity_id"
                                id="entity_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('entity_id') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            >
                                <option value="">-- Pilih Entitas --</option>

                                @foreach ($entities as $entity)
                                    <option value="{{ $entity->id }}" @selected(old('entity_id') == $entity->id)>
                                        {{ $entity->code }} - {{ $entity->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('entity_id')
                                <p class="mt-1 text-[10px] text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="item_id" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                Barang
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                name="item_id"
                                id="item_id"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('item_id') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            >
                                <option value="">-- Pilih Barang --</option>

                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>
                                        {{ $item->code }} - {{ $item->name }}
                                        @if ($item->category)
                                            | {{ $item->category->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('item_id')
                                <p class="mt-1 text-[10px] text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label for="date" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                    Tanggal Masuk
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="date"
                                    id="date"
                                    value="{{ old('date', date('Y-m-d')) }}"
                                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('date') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                >

                                @error('date')
                                    <p class="mt-1 text-[10px] text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label for="quantity" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                    Jumlah Masuk
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="quantity"
                                    id="quantity"
                                    min="1"
                                    value="{{ old('quantity') }}"
                                    placeholder="Contoh: 10"
                                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('quantity') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                >

                                @error('quantity')
                                    <p class="mt-1 text-[10px] text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="supplier" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                Supplier
                            </label>

                            <input
                                type="text"
                                name="supplier"
                                id="supplier"
                                value="{{ old('supplier') }}"
                                placeholder="Contoh: Toko ATK Maju"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('supplier') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            >

                            @error('supplier')
                                <p class="mt-1 text-[10px] text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="note" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                Keterangan
                            </label>

                            <textarea
                                name="note"
                                id="note"
                                rows="3"
                                placeholder="Catatan tambahan jika ada"
                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('note') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            >{{ old('note') }}</textarea>

                            @error('note')
                                <p class="mt-1 text-[10px] text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="rounded-md border border-indigo-100 bg-indigo-50 px-3 py-2 text-[11px] text-indigo-700">
                            <div class="flex gap-2">
                                <svg class="mt-0.5 h-4 w-4 flex-none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25h1.5v6h-1.5v-6Zm0-4.5h1.5v1.5h-1.5v-1.5ZM12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" />
                                </svg>
                                <p>
                                    Setelah disimpan, stok barang akan bertambah hanya pada entitas yang dipilih.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                        <a
                            href="{{ route('stock-ins.index') }}"
                            class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19 3 12m0 0 7-7M3 12h18" />
                            </svg>
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5 9.5 17 19 7" />
                            </svg>
                            Simpan Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>