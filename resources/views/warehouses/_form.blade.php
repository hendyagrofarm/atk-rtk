@php
    $isEdit = isset($warehouse);
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    {{-- Kode Gudang --}}
    <div>
        <label
            for="code"
            class="mb-1.5 block text-[11px] font-semibold text-slate-700"
        >
            Kode Gudang
            <span class="text-red-500">*</span>
        </label>

        <input
            type="text"
            name="code"
            id="code"
            value="{{ old('code', $warehouse->code ?? '') }}"
            maxlength="50"
            autocomplete="off"
            class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs uppercase shadow-sm focus:border-sky-500 focus:ring-sky-500"
            placeholder="Contoh: GD-PABRIK"
            required
        >

        <p class="mt-1 text-[10px] text-slate-500">
            Gunakan kode singkat dan unik. Contoh: GD-PABRIK atau GD-HO.
        </p>

        @error('code')
            <p class="mt-1 text-[10px] font-medium text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Nama Gudang --}}
    <div>
        <label
            for="name"
            class="mb-1.5 block text-[11px] font-semibold text-slate-700"
        >
            Nama Gudang
            <span class="text-red-500">*</span>
        </label>

        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $warehouse->name ?? '') }}"
            maxlength="150"
            autocomplete="off"
            class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-sky-500 focus:ring-sky-500"
            placeholder="Contoh: Gudang Pabrik"
            required
        >

        <p class="mt-1 text-[10px] text-slate-500">
            Masukkan nama gudang yang mudah dikenali.
        </p>

        @error('name')
            <p class="mt-1 text-[10px] font-medium text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Lokasi --}}
    <div class="md:col-span-2">
        <label
            for="location"
            class="mb-1.5 block text-[11px] font-semibold text-slate-700"
        >
            Lokasi Gudang
        </label>

        <input
            type="text"
            name="location"
            id="location"
            value="{{ old('location', $warehouse->location ?? '') }}"
            maxlength="255"
            autocomplete="off"
            class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-sky-500 focus:ring-sky-500"
            placeholder="Contoh: Area Pabrik, Head Office, atau alamat gudang"
        >

        @error('location')
            <p class="mt-1 text-[10px] font-medium text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Keterangan --}}
    <div class="md:col-span-2">
        <label
            for="description"
            class="mb-1.5 block text-[11px] font-semibold text-slate-700"
        >
            Keterangan
        </label>

        <textarea
            name="description"
            id="description"
            rows="4"
            maxlength="1000"
            class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-sky-500 focus:ring-sky-500"
            placeholder="Masukkan keterangan tambahan mengenai gudang"
        >{{ old('description', $warehouse->description ?? '') }}</textarea>

        <div class="mt-1 flex items-center justify-between gap-2">
            <p class="text-[10px] text-slate-500">
                Maksimal 1.000 karakter.
            </p>

            <p
                id="description-counter"
                class="text-[10px] text-slate-400"
            >
                0 / 1000
            </p>
        </div>

        @error('description')
            <p class="mt-1 text-[10px] font-medium text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Status Aktif --}}
    <div class="md:col-span-2">
        <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3">
            <label class="flex cursor-pointer items-start gap-3">
                <input
                    type="hidden"
                    name="is_active"
                    value="0"
                >

                <input
                    type="checkbox"
                    name="is_active"
                    id="is_active"
                    value="1"
                    class="mt-0.5 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                    @checked(old('is_active', $warehouse->is_active ?? true))
                >

                <span>
                    <span class="block text-[11px] font-semibold text-slate-800">
                        Gudang Aktif
                    </span>

                    <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                        Gudang aktif dapat digunakan pada transaksi stok dan pengajuan.
                        Gudang nonaktif tetap tersimpan tetapi tidak dapat dipilih untuk transaksi baru.
                    </span>
                </span>
            </label>
        </div>

        @error('is_active')
            <p class="mt-1 text-[10px] font-medium text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const codeInput = document.getElementById('code');
        const descriptionInput = document.getElementById('description');
        const descriptionCounter = document.getElementById('description-counter');

        if (codeInput) {
            codeInput.addEventListener('input', function () {
                this.value = this.value
                    .toUpperCase()
                    .replace(/\s+/g, '-')
                    .replace(/[^A-Z0-9\-]/g, '');
            });
        }

        function updateDescriptionCounter() {
            if (!descriptionInput || !descriptionCounter) {
                return;
            }

            descriptionCounter.textContent =
                descriptionInput.value.length + ' / 1000';
        }

        if (descriptionInput) {
            descriptionInput.addEventListener(
                'input',
                updateDescriptionCounter
            );

            updateDescriptionCounter();
        }
    });
</script>