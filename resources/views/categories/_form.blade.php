<div>
    <label for="name" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
        Nama Kategori
        <span class="text-red-500">*</span>
    </label>

    <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h2A2.5 2.5 0 0 1 11 6.5v2A2.5 2.5 0 0 1 8.5 11h-2A2.5 2.5 0 0 1 4 8.5v-2ZM13 6.5A2.5 2.5 0 0 1 15.5 4h2A2.5 2.5 0 0 1 20 6.5v2a2.5 2.5 0 0 1-2.5 2.5h-2A2.5 2.5 0 0 1 13 8.5v-2ZM4 15.5A2.5 2.5 0 0 1 6.5 13h2a2.5 2.5 0 0 1 2.5 2.5v2A2.5 2.5 0 0 1 8.5 20h-2A2.5 2.5 0 0 1 4 17.5v-2ZM13 15.5a2.5 2.5 0 0 1 2.5-2.5h2a2.5 2.5 0 0 1 2.5 2.5v2a2.5 2.5 0 0 1-2.5 2.5h-2a2.5 2.5 0 0 1-2.5-2.5v-2Z" />
            </svg>
        </div>

        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $category->name ?? '') }}"
            class="block w-full rounded-md border-slate-300 py-1.5 pl-8 pr-3 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
            placeholder="Contoh: Alat Tulis"
            autocomplete="off"
        >
    </div>

    @error('name')
        <p class="mt-1 flex items-center gap-1 text-[10px] text-red-600">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
            </svg>
            {{ $message }}
        </p>
    @enderror
</div>

<div>
    <label for="description" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
        Deskripsi
    </label>

    <textarea
        name="description"
        id="description"
        rows="3"
        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
        placeholder="Contoh: Kategori untuk barang alat tulis kantor"
    >{{ old('description', $category->description ?? '') }}</textarea>

    <p class="mt-1 text-[10px] leading-4 text-slate-500">
        Deskripsi bersifat opsional.
    </p>

    @error('description')
        <p class="mt-1 flex items-center gap-1 text-[10px] text-red-600">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
            </svg>
            {{ $message }}
        </p>
    @enderror
</div>