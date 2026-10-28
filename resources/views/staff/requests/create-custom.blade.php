<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-[13px] font-semibold text-white">
                    Formulir Pengajuan Barang (FPB)
                </h2>

                <p class="text-[10px] text-white/80">
                    Ajukan barang yang belum tersedia di stok. Isian mengikuti kolom pada formulir cetak.
                </p>
            </div>

            <a href="{{ route('staff.requests.index') }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-white/20">
                Kembali
            </a>
        </div>
    </x-slot>

    @php
        $oldItems = old('items', [
            [
                'custom_item_name' => '',
                'custom_category' => '',
                'budget_type' => '',
                'brand_type' => '',
                'unit' => '',
                'quantity' => 1,
                'usage_purpose' => '',
                'note' => '',
                'specification' => '',
                'estimated_price' => '',
                'product_url' => '',
            ],
        ]);

        $groupOptions = ['ATK', 'RTK', 'Sarana Prasarana'];

        $fieldClass = 'w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500';
        $flow = $fixedSteps->map(fn ($step) => $step->position->name)->implode(' → ');
    @endphp

    <div class="space-y-3">
        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2">
                <p class="text-[11px] font-semibold text-red-800">Terjadi kesalahan</p>
                <p class="text-[10px] text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2">
                <p class="text-[11px] font-semibold text-red-800">Data belum lengkap:</p>

                <ul class="mt-1 list-inside list-disc text-[10px] text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
            <p class="text-[11px] font-semibold text-amber-900">Informasi</p>

            <p class="text-[10px] leading-4 text-amber-800">
                Pengajuan ini untuk barang yang belum tersedia di stok; saat disetujui, stok tidak berkurang.
                Persetujuan berjalan berurutan: atasan langsung{{ $flow ? ' → ' . $flow : '' }}.
                FPB dapat dicetak setelah semua tahap disetujui, lengkap dengan tanda tangan digital.
            </p>
        </div>

        <form
            action="{{ route('staff.requests.custom.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm"
        >
            @csrf

            <div class="space-y-5 p-4">
                {{-- 1. Data FPB (bagian header formulir) --}}
                <section class="space-y-3">
                    <h3 class="text-xs font-bold text-slate-800">1. Data Pengajuan</h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label for="entity_id" class="mb-1 block text-[11px] font-semibold text-slate-700">
                                Perusahaan / Entitas <span class="text-red-500">*</span>
                            </label>

                            <select name="entity_id" id="entity_id" class="{{ $fieldClass }}" required>
                                <option value="">-- Pilih Entitas --</option>

                                @foreach ($entities as $entity)
                                    <option value="{{ $entity->id }}" @selected(old('entity_id') == $entity->id)>
                                        {{ $entity->code }} - {{ $entity->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="department" class="mb-1 block text-[11px] font-semibold text-slate-700">
                                Divisi / Departemen <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                name="department"
                                id="department"
                                value="{{ old('department') }}"
                                maxlength="150"
                                class="{{ $fieldClass }}"
                                placeholder="Contoh: Affair & Legal - HSE"
                                required
                            >
                        </div>
                    </div>

                    <div>
                        <label for="note" class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Catatan Pengajuan
                        </label>

                        <textarea
                            name="note"
                            id="note"
                            rows="2"
                            class="{{ $fieldClass }}"
                            placeholder="Contoh: Pengadaan fasilitas tanggap darurat K3 untuk area kerja lantai 2 dan 3"
                        >{{ old('note') }}</textarea>
                    </div>
                </section>

                {{-- 2. Penandatangan (kotak tanda tangan pada formulir) --}}
                <section class="space-y-3 rounded-md border border-slate-200 bg-slate-50 p-3">
                    <div>
                        <h3 class="text-xs font-bold text-slate-800">2. Penandatangan FPB</h3>
                        <p class="text-[10px] text-slate-500">
                            Kotak tanda tangan pada formulir. Yang dipilih dari sistem menyetujui secara digital;
                            tanda tangan PNG mereka tercetak otomatis.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        {{-- Diajukan oleh (PIC) --}}
                        <div class="space-y-2">
                            <p class="text-[11px] font-semibold text-slate-700">
                                Diajukan oleh (PIC)
                                @if ($canSetPic)
                                    <span class="text-red-500">*</span>
                                @endif
                            </p>

                            @if ($canSetPic)
                                <input
                                    type="search"
                                    id="pic_filter"
                                    class="{{ $fieldClass }}"
                                    placeholder="Cari nama PIC di sistem..."
                                    autocomplete="off"
                                >

                                <select name="pic_user_id" id="pic_user_id" class="{{ $fieldClass }}">
                                    <option value="">-- PIC tanpa akun (isi manual) --</option>
                                    @foreach ($picCandidates as $candidate)
                                        <option
                                            value="{{ $candidate['id'] }}"
                                            data-label="{{ $candidate['name'] }}{{ $candidate['position'] ? ' - ' . $candidate['position'] : '' }}"
                                            data-has-signature="{{ $candidate['has_signature'] ? '1' : '0' }}"
                                            @selected((string) old('pic_user_id') === (string) $candidate['id'])
                                        >
                                            {{ $candidate['name'] }}{{ $candidate['position'] ? ' - ' . $candidate['position'] : '' }}{{ $candidate['has_signature'] ? '' : ' (belum ada TTD)' }}
                                        </option>
                                    @endforeach
                                </select>

                                <p id="pic_hint" class="text-[10px] leading-4 text-slate-500"></p>

                                <div id="pic_manual" class="space-y-2">
                                    <p class="text-[10px] text-slate-500">PIC tidak punya akun? Isi manual (tanda tangan basah).</p>

                                    <input
                                        type="text"
                                        name="pic_name"
                                        value="{{ old('pic_name') }}"
                                        maxlength="150"
                                        class="{{ $fieldClass }}"
                                        placeholder="Nama PIC"
                                        autocomplete="off"
                                    >

                                    <input
                                        type="text"
                                        name="pic_position"
                                        value="{{ old('pic_position') }}"
                                        maxlength="150"
                                        class="{{ $fieldClass }}"
                                        placeholder="Jabatan PIC (contoh: Staff K3L)"
                                    >
                                </div>

                                <div id="pic_upload" class="hidden rounded-md border border-amber-200 bg-amber-50 p-2">
                                    <label class="mb-1 block text-[10px] font-semibold text-amber-800">
                                        PIC ini belum punya tanda tangan digital. Unggah tanda tangan (PNG, maks. 1 MB)
                                    </label>

                                    <input
                                        type="file"
                                        name="pic_signature"
                                        accept="image/png"
                                        class="block w-full rounded-md border border-amber-200 bg-white px-2 py-1 text-xs"
                                    >

                                    <p class="mt-1 text-[10px] text-amber-700">
                                        Tersimpan di profil PIC dan dipakai pada FPB berikutnya.
                                    </p>
                                </div>
                            @else
                                <div class="rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                                    <p class="font-semibold">{{ $user->name }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $user->position?->name ?? 'Jabatan belum diatur' }}</p>
                                </div>

                                @if ($ownHasSignature)
                                    <p class="text-[10px] text-emerald-700">
                                        Tanda tangan digital Anda akan tercetak otomatis pada kotak "Diajukan oleh".
                                    </p>
                                @else
                                    <p class="text-[10px] text-amber-700">
                                        Anda belum punya tanda tangan digital, jadi kotak "Diajukan oleh" tercetak kosong.
                                        Minta admin mengunggah tanda tangan PNG Anda.
                                    </p>
                                @endif
                            @endif
                        </div>

                        {{-- Diketahui oleh (atasan langsung) --}}
                        <div class="space-y-2">
                            <p class="text-[11px] font-semibold text-slate-700">Diketahui oleh (Atasan Langsung)</p>

                            <input
                                type="search"
                                id="superior_filter"
                                class="{{ $fieldClass }}"
                                placeholder="Cari nama atasan di sistem..."
                                autocomplete="off"
                            >

                            <select name="superior_user_id" id="superior_user_id" class="{{ $fieldClass }}">
                                <option value="">-- Tanpa persetujuan digital (isi manual) --</option>
                                @foreach ($superiorCandidates as $candidate)
                                    <option
                                        value="{{ $candidate['id'] }}"
                                        data-label="{{ $candidate['name'] }}{{ $candidate['position'] ? ' - ' . $candidate['position'] : '' }}"
                                        data-has-signature="{{ $candidate['has_signature'] ? '1' : '0' }}"
                                        @selected((string) old('superior_user_id') === (string) $candidate['id'])
                                    >
                                        {{ $candidate['name'] }}{{ $candidate['position'] ? ' - ' . $candidate['position'] : '' }}{{ $candidate['has_signature'] ? '' : ' (belum ada TTD)' }}
                                    </option>
                                @endforeach
                            </select>

                            <p id="superior_hint" class="text-[10px] leading-4 text-slate-500"></p>

                            <div id="superior_manual" class="space-y-2">
                                <p class="text-[10px] text-slate-500">Atasan tidak punya akun? Isi manual (tanda tangan basah).</p>

                                <input
                                    type="text"
                                    name="superior_name"
                                    value="{{ old('superior_name') }}"
                                    maxlength="150"
                                    class="{{ $fieldClass }}"
                                    placeholder="Nama atasan langsung"
                                    autocomplete="off"
                                >

                                <input
                                    type="text"
                                    name="superior_position"
                                    value="{{ old('superior_position') }}"
                                    maxlength="150"
                                    class="{{ $fieldClass }}"
                                    placeholder="Jabatan atasan langsung (contoh: Head of Corporate Affair & Legal)"
                                >
                            </div>

                            @if ($canSetPic)
                                <div id="superior_upload" class="hidden rounded-md border border-amber-200 bg-amber-50 p-2">
                                    <label class="mb-1 block text-[10px] font-semibold text-amber-800">
                                        Atasan ini belum punya tanda tangan digital. Unggah tanda tangan (PNG, maks. 1 MB)
                                    </label>

                                    <input
                                        type="file"
                                        name="superior_signature"
                                        accept="image/png"
                                        class="block w-full rounded-md border border-amber-200 bg-white px-2 py-1 text-xs"
                                    >

                                    <p class="mt-1 text-[10px] text-amber-700">
                                        Tersimpan di profil atasan dan dipakai pada FPB berikutnya. Tanpa tanda tangan, kotaknya tercetak kosong.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($fixedSteps->isNotEmpty())
                        <p class="text-[10px] text-slate-500">
                            Tahap berikutnya (tetap untuk semua FPB):
                            @foreach ($fixedSteps as $step)
                                <span class="font-semibold text-slate-700">{{ $step->label }} {{ $step->position->name }}</span>{{ $loop->last ? '.' : ',' }}
                            @endforeach
                        </p>
                    @endif
                </section>

                {{-- 3. Daftar barang (tabel pada formulir) --}}
                <section class="space-y-3">
                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-xs font-bold text-slate-800">3. Daftar Barang</h3>
                            <p class="text-[10px] text-slate-500">
                                Kolom sama dengan tabel formulir: Nama Barang, Type/Merk, Jumlah, Satuan, Digunakan Untuk, Keterangan.
                            </p>
                        </div>

                        <button
                            type="button"
                            onclick="addCustomItem()"
                            class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-700"
                        >
                            + Tambah Barang
                        </button>
                    </div>

                    <div id="custom-items-wrapper" class="space-y-3">
                        @foreach ($oldItems as $index => $oldItem)
                            <div class="custom-item rounded-md border border-slate-200 bg-slate-50 p-3">
                                <div class="mb-3 flex items-center justify-between">
                                    <p class="item-number text-[11px] font-bold text-slate-800">Barang {{ $index + 1 }}</p>

                                    <button
                                        type="button"
                                        onclick="removeCustomItem(this)"
                                        class="rounded-md border border-red-200 bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700 hover:bg-red-100"
                                    >
                                        Hapus
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                                    <div class="md:col-span-3">
                                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                            Nama Barang <span class="text-red-500">*</span>
                                        </label>

                                        <input
                                            type="text"
                                            name="items[{{ $index }}][custom_item_name]"
                                            value="{{ $oldItem['custom_item_name'] ?? '' }}"
                                            class="{{ $fieldClass }}"
                                            placeholder="Contoh: Emergency Eye Wash Portable"
                                            required
                                        >
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                            Type / Merk
                                        </label>

                                        <input
                                            type="text"
                                            name="items[{{ $index }}][brand_type]"
                                            value="{{ $oldItem['brand_type'] ?? '' }}"
                                            maxlength="255"
                                            class="{{ $fieldClass }}"
                                            placeholder="Contoh: Unicare 30L"
                                        >
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                            Jenis Anggaran <span class="text-red-500">*</span>
                                        </label>

                                        <select
                                            name="items[{{ $index }}][budget_type]"
                                            class="budget-select {{ $fieldClass }}"
                                            required
                                        >
                                            <option value="">-- Pilih --</option>
                                            <option value="OPEX" @selected(($oldItem['budget_type'] ?? '') === 'OPEX')>OPEX (operasional)</option>
                                            <option value="CAPEX" @selected(($oldItem['budget_type'] ?? '') === 'CAPEX')>CAPEX (investasi/aset)</option>
                                        </select>
                                    </div>

                                    <div class="group-wrapper md:col-span-2 {{ ($oldItem['budget_type'] ?? '') === 'CAPEX' ? 'hidden' : '' }}">
                                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                            Kelompok OPEX
                                        </label>

                                        <select
                                            name="items[{{ $index }}][custom_category]"
                                            class="group-select {{ $fieldClass }}"
                                            @disabled(($oldItem['budget_type'] ?? '') === 'CAPEX')
                                        >
                                            <option value="">-- Pilih Kelompok --</option>
                                            @foreach ($groupOptions as $group)
                                                <option value="{{ $group }}" @selected(($oldItem['custom_category'] ?? '') === $group)>{{ $group }}</option>
                                            @endforeach

                                            @if (filled($oldItem['custom_category'] ?? '') && ! in_array($oldItem['custom_category'], $groupOptions, true))
                                                <option value="{{ $oldItem['custom_category'] }}" selected>{{ $oldItem['custom_category'] }}</option>
                                            @endif
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                            Jumlah <span class="text-red-500">*</span>
                                        </label>

                                        <input
                                            type="number"
                                            name="items[{{ $index }}][quantity]"
                                            value="{{ $oldItem['quantity'] ?? 1 }}"
                                            min="1"
                                            class="quantity-input {{ $fieldClass }}"
                                            placeholder="Contoh: 2"
                                            oninput="calculateEstimatedTotal()"
                                            required
                                        >
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                            Satuan <span class="text-red-500">*</span>
                                        </label>

                                        <input
                                            type="text"
                                            name="items[{{ $index }}][unit]"
                                            value="{{ $oldItem['unit'] ?? '' }}"
                                            class="{{ $fieldClass }}"
                                            placeholder="Contoh: pcs"
                                            required
                                        >
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                            Digunakan Untuk
                                        </label>

                                        <textarea
                                            name="items[{{ $index }}][usage_purpose]"
                                            rows="3"
                                            maxlength="1000"
                                            class="{{ $fieldClass }}"
                                            placeholder="Contoh: Sebagai fasilitas tanggap darurat pembilasan mata pada area kerja lantai 2 dan 3"
                                        >{{ $oldItem['usage_purpose'] ?? '' }}</textarea>
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                            Keterangan
                                        </label>

                                        <textarea
                                            name="items[{{ $index }}][note]"
                                            rows="3"
                                            maxlength="500"
                                            class="{{ $fieldClass }}"
                                            placeholder="Contoh: Pengadaan untuk memenuhi kebutuhan eyewash pada area dengan potensi paparan bahan kimia"
                                        >{{ $oldItem['note'] ?? '' }}</textarea>
                                    </div>
                                </div>

                                <details class="mt-3 rounded-md border border-slate-200 bg-white px-3 py-2">
                                    <summary class="cursor-pointer text-[10px] font-semibold text-slate-600">
                                        Informasi tambahan (opsional, tidak tercetak di FPB)
                                    </summary>

                                    <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div class="md:col-span-2">
                                            <label class="mb-1 block text-[10px] font-semibold text-slate-700">Spesifikasi</label>

                                            <textarea
                                                name="items[{{ $index }}][specification]"
                                                rows="2"
                                                class="{{ $fieldClass }}"
                                                placeholder="Contoh: Kapasitas 30 liter, tangki stainless, nozzle ganda"
                                            >{{ $oldItem['specification'] ?? '' }}</textarea>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                                Estimasi Harga per Satuan
                                            </label>

                                            <input
                                                type="number"
                                                name="items[{{ $index }}][estimated_price]"
                                                value="{{ $oldItem['estimated_price'] ?? '' }}"
                                                min="0"
                                                step="1"
                                                class="estimated-price-input {{ $fieldClass }}"
                                                placeholder="Contoh: 3500000"
                                                oninput="calculateEstimatedTotal()"
                                            >
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-slate-700">Link Produk</label>

                                            <input
                                                type="url"
                                                name="items[{{ $index }}][product_url]"
                                                value="{{ $oldItem['product_url'] ?? '' }}"
                                                class="{{ $fieldClass }}"
                                                placeholder="https://..."
                                            >
                                        </div>
                                    </div>
                                </details>
                            </div>
                        @endforeach
                    </div>

                    <div id="estimated-wrapper" class="hidden justify-end">
                        <div class="w-full rounded-md border border-slate-200 bg-slate-50 px-4 py-2 sm:w-72">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                Estimasi Total (tidak tercetak)
                            </p>

                            <p id="estimated-total" class="mt-0.5 text-sm font-bold text-slate-700">Rp 0</p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                <a
                    href="{{ route('staff.requests.index') }}"
                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-100"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-700"
                >
                    Ajukan FPB
                </button>
            </div>
        </form>
    </div>

    <script>
        /*
         * Pemilih orang (PIC dan atasan): cari, pilih dari sistem, isian manual disembunyikan,
         * dan kolom unggah tanda tangan muncul bila orang itu belum punya tanda tangan.
         */
        function setupPersonPicker(prefix, defaultHint, selectedHint) {
            const select = document.getElementById(prefix + '_user_id');

            if (! select) {
                return null;
            }

            const filter = document.getElementById(prefix + '_filter');
            const manual = document.getElementById(prefix + '_manual');
            const upload = document.getElementById(prefix + '_upload');
            const hint = document.getElementById(prefix + '_hint');
            const options = Array.from(select.options).map(function (option) { return option.cloneNode(true); });

            function apply() {
                const chosen = select.options[select.selectedIndex];
                const hasUser = chosen && chosen.value !== '';

                manual.classList.toggle('hidden', hasUser);

                if (upload) {
                    upload.classList.toggle('hidden', ! (hasUser && chosen.dataset.hasSignature === '0'));
                }

                hint.textContent = hasUser ? selectedHint(chosen.dataset.label) : defaultHint;
            }

            filter.addEventListener('input', function () {
                const keyword = filter.value.trim().toLowerCase();
                const current = select.value;

                select.innerHTML = '';

                options.forEach(function (option) {
                    const isPlaceholder = option.value === '';
                    const matched = keyword === '' || option.textContent.toLowerCase().includes(keyword);

                    if (isPlaceholder || option.value === current || matched) {
                        const copy = option.cloneNode(true);
                        copy.disabled = option.disabled;
                        copy.selected = option.value === current;
                        select.appendChild(copy);
                    }
                });

                apply();
            });

            select.addEventListener('change', apply);
            apply();

            return {
                select: select,
                options: options,
                // Orang yang sama tidak boleh menjadi PIC sekaligus atasan.
                blockUser: function (userId) {
                    options.forEach(function (option) { option.disabled = option.value !== '' && option.value === userId; });
                    Array.from(select.options).forEach(function (option) {
                        option.disabled = option.value !== '' && option.value === userId;
                    });
                },
            };
        }

        document.addEventListener('DOMContentLoaded', function () {
            const pic = setupPersonPicker(
                'pic',
                'Pilih PIC dari sistem agar tanda tangan digitalnya tercetak otomatis, atau isi manual di bawah.',
                function (label) { return label + ' menjadi PIC (Diajukan oleh); tanda tangan digitalnya tercetak otomatis.'; }
            );

            const superior = setupPersonPicker(
                'superior',
                'Atasan yang dipilih dari sistem menyetujui FPB secara digital lebih dulu, sebelum tahap berikutnya.',
                function (label) { return label + ' akan menyetujui FPB ini secara digital sebelum tahap berikutnya.'; }
            );

            if (pic && superior) {
                const sync = function () {
                    superior.blockUser(pic.select.value);
                    pic.blockUser(superior.select.value);
                };

                pic.select.addEventListener('change', sync);
                superior.select.addEventListener('change', sync);
                sync();
            }

            refreshItemNumbers();
            calculateEstimatedTotal();
            document.querySelectorAll('.custom-item').forEach(syncGroupVisibility);
        });

        /* ----- Daftar barang ----- */
        let customItemIndex = {{ count($oldItems) }};

        // Kelompok (ATK/RTK/Sarana Prasarana) hanya relevan untuk OPEX; CAPEX tidak dikelompokkan.
        function syncGroupVisibility(item) {
            const budget = item.querySelector('.budget-select');
            const wrapper = item.querySelector('.group-wrapper');
            const group = item.querySelector('.group-select');
            const isCapex = budget.value === 'CAPEX';

            wrapper.classList.toggle('hidden', isCapex);
            group.disabled = isCapex;

            if (isCapex) {
                group.value = '';
            }
        }

        document.getElementById('custom-items-wrapper').addEventListener('change', function (event) {
            if (event.target.classList.contains('budget-select')) {
                syncGroupVisibility(event.target.closest('.custom-item'));
            }
        });

        function addCustomItem() {
            const wrapper = document.getElementById('custom-items-wrapper');
            const newItem = wrapper.querySelector('.custom-item').cloneNode(true);

            newItem.querySelectorAll('input, textarea, select').forEach(function (element) {
                const oldName = element.getAttribute('name');

                if (oldName) {
                    element.setAttribute('name', oldName.replace(/\[\d+\]/, '[' + customItemIndex + ']'));
                }

                if (element.type === 'number' && oldName && oldName.includes('[quantity]')) {
                    element.value = 1;
                } else if (element.tagName === 'SELECT') {
                    element.selectedIndex = 0;
                } else {
                    element.value = '';
                }
            });

            newItem.querySelector('details').open = false;
            wrapper.appendChild(newItem);
            customItemIndex++;

            syncGroupVisibility(newItem);
            refreshItemNumbers();
            calculateEstimatedTotal();
        }

        function removeCustomItem(button) {
            if (document.querySelectorAll('.custom-item').length <= 1) {
                alert('Minimal harus ada satu barang dalam pengajuan.');
                return;
            }

            button.closest('.custom-item').remove();

            refreshItemNumbers();
            calculateEstimatedTotal();
        }

        function refreshItemNumbers() {
            document.querySelectorAll('.custom-item').forEach(function (item, index) {
                item.querySelector('.item-number').textContent = 'Barang ' + (index + 1);
            });
        }

        function calculateEstimatedTotal() {
            let total = 0;

            document.querySelectorAll('.custom-item').forEach(function (item) {
                const quantity = parseFloat(item.querySelector('.quantity-input')?.value || 0);
                const price = parseFloat(item.querySelector('.estimated-price-input')?.value || 0);

                total += quantity * price;
            });

            const box = document.getElementById('estimated-wrapper');
            box.classList.toggle('hidden', total <= 0);
            box.classList.toggle('flex', total > 0);

            document.getElementById('estimated-total').textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(total);
        }
    </script>
</x-app-layout>
