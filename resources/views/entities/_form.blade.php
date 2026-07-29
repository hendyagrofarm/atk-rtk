@csrf

<div class="space-y-4">
    <div>
        <label for="code" class="mb-1 block text-[11px] font-semibold text-slate-700">
            Kode Entitas <span class="text-red-500">*</span>
        </label>

        <input
            type="text"
            id="code"
            name="code"
            maxlength="20"
            value="{{ old('code', $entity->code ?? '') }}"
            placeholder="Contoh: ANR"
            required
            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >

        @error('code')
            <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="name" class="mb-1 block text-[11px] font-semibold text-slate-700">
            Nama Entitas <span class="text-red-500">*</span>
        </label>

        <input
            type="text"
            id="name"
            name="name"
            maxlength="255"
            value="{{ old('name', $entity->name ?? '') }}"
            placeholder="Contoh: PT Agrofarm Nusa Raya"
            required
            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >

        @error('name')
            <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
        <input type="hidden" name="is_active" value="0">

        <input
            type="checkbox"
            name="is_active"
            value="1"
            @checked(old('is_active', $entity->is_active ?? true))
            class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
        >

        <span class="text-[11px] font-semibold text-slate-700">
            Entitas aktif dan dapat digunakan
        </span>
    </label>
</div>
