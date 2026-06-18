@csrf

<div class="space-y-4 px-4 py-4">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div>
            <label for="code" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Kode Barang
                <span class="text-red-500">*</span>
            </label>

            <input
                type="text"
                name="code"
                id="code"
                value="{{ old('code', $item->code ?? '') }}"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('code') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                placeholder="Contoh: ATK001"
                autocomplete="off"
            >

            @error('code')
                <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="name" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Nama Barang
                <span class="text-red-500">*</span>
            </label>

            <input
                type="text"
                name="name"
                id="name"
                value="{{ old('name', $item->name ?? '') }}"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                placeholder="Contoh: Pulpen Biru"
                autocomplete="off"
            >

            @error('name')
                <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="category_id" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Kategori
                <span class="text-red-500">*</span>
            </label>

            <select
                name="category_id"
                id="category_id"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('category_id') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
            >
                <option value="">-- Pilih Kategori --</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id ?? '') == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            @error('category_id')
                <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="type" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Jenis Barang
                <span class="text-red-500">*</span>
            </label>

            <select
                name="type"
                id="type"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('type') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
            >
                <option value="">-- Pilih Jenis --</option>
                <option value="ATK" @selected(old('type', $item->type ?? '') == 'ATK')>ATK</option>
                <option value="RTK" @selected(old('type', $item->type ?? '') == 'RTK')>RTK</option>
            </select>

            @error('type')
                <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="unit" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Satuan
                <span class="text-red-500">*</span>
            </label>

            <input
                type="text"
                name="unit"
                id="unit"
                value="{{ old('unit', $item->unit ?? '') }}"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('unit') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                placeholder="Contoh: pcs, box, pack"
                autocomplete="off"
            >

            @error('unit')
                <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="storage_location" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Lokasi Penyimpanan
            </label>

            <input
                type="text"
                name="storage_location"
                id="storage_location"
                value="{{ old('storage_location', $item->storage_location ?? '') }}"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('storage_location') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                placeholder="Contoh: Lemari ATK"
                autocomplete="off"
            >

            @error('storage_location')
                <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="current_stock" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Stok Saat Ini
            </label>

            <input
                type="number"
                name="current_stock"
                id="current_stock"
                min="0"
                value="{{ old('current_stock', $item->current_stock ?? 0) }}"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('current_stock') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
            >

            @error('current_stock')
                <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="minimum_stock" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                Stok Minimum
            </label>

            <input
                type="number"
                name="minimum_stock"
                id="minimum_stock"
                min="0"
                value="{{ old('minimum_stock', $item->minimum_stock ?? 0) }}"
                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('minimum_stock') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
            >

            @error('minimum_stock')
                <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="description" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
            Keterangan
        </label>

        <textarea
            name="description"
            id="description"
            rows="3"
            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
            placeholder="Keterangan tambahan jika ada"
        >{{ old('description', $item->description ?? '') }}</textarea>

        @error('description')
            <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
        <label class="inline-flex items-center gap-2">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('is_active', $item->is_active ?? true))
            >

            <span class="text-[11px] font-medium text-slate-700">
                Barang aktif
            </span>
        </label>

        <p class="mt-1 text-[10px] leading-4 text-slate-500">
            Jika dicentang, barang dapat digunakan dalam transaksi dan pengajuan.
        </p>
    </div>
</div>

<div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
    <a
        href="{{ route('items.index') }}"
        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
    >
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19 3 12m0 0 7-7M3 12h18" />
        </svg>
        Batal
    </a>

    <button
        type="submit"
        class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-indigo-700"
    >
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5 9.5 17 19 7" />
        </svg>
        Simpan
    </button>
</div>