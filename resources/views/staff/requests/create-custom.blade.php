<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-[13px] font-semibold text-white">
                    Pengajuan Barang Baru / Custom
                </h2>

                <p class="text-[10px] text-white/80">
                    Ajukan barang yang belum tersedia pada Master Barang atau stok.
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
                'usage_purpose' => '',
                'unit' => '',
                'quantity' => 1,
                'estimated_price' => '',
                'product_url' => '',
                'specification' => '',
                'note' => '',
            ],
        ]);
    @endphp

    <div class="space-y-3">
        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2">
                <p class="text-[11px] font-semibold text-red-800">
                    Terjadi kesalahan
                </p>

                <p class="text-[10px] text-red-700">
                    {{ session('error') }}
                </p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2">
                <p class="text-[11px] font-semibold text-red-800">
                    Data belum lengkap:
                </p>

                <ul class="mt-1 list-inside list-disc text-[10px] text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
            <p class="text-[11px] font-semibold text-amber-900">
                Informasi
            </p>

            <p class="text-[10px] leading-4 text-amber-800">
                Pengajuan ini digunakan untuk barang yang belum tersedia di stok.
                Saat disetujui, stok tidak akan berkurang.
            </p>
        </div>

        <form
            action="{{ route('staff.requests.custom.store') }}"
            method="POST"
            class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm"
        >
            @csrf

            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-bold text-slate-800">
                    Form Pengajuan Custom
                </h3>

                <p class="text-[10px] text-slate-500">
                    Isi entitas dan daftar barang yang diajukan.
                </p>
            </div>

            <div class="space-y-4 p-4">
                <div>
                    <label
                        for="entity_id"
                        class="mb-1 block text-[11px] font-semibold text-slate-700"
                    >
                        Entitas
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="entity_id"
                        id="entity_id"
                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                        required
                    >
                        <option value="">
                            -- Pilih Entitas --
                        </option>

                        @foreach ($entities as $entity)
                            <option
                                value="{{ $entity->id }}"
                                @selected(old('entity_id') == $entity->id)
                            >
                                {{ $entity->code }} - {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        for="department"
                        class="mb-1 block text-[11px] font-semibold text-slate-700"
                    >
                        Divisi / Departemen
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="department"
                        id="department"
                        value="{{ old('department') }}"
                        maxlength="150"
                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                        placeholder="Contoh: Affair & Legal - HSE"
                        required
                    >
                </div>

                <div class="space-y-3 rounded-md border border-slate-200 bg-slate-50 p-3">
                    <div>
                        <h4 class="text-xs font-bold text-slate-800">Penandatangan FPB</h4>
                        <p class="text-[10px] text-slate-500">
                            Kotak tanda tangan basah pada formulir. Spv. GA dan Manager HCGA mengikuti pengaturan Alur Approval.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="space-y-2">
                            <p class="text-[11px] font-semibold text-slate-700">
                                Diajukan oleh (PIC)
                                @if ($canSetPic)
                                    <span class="text-red-500">*</span>
                                @endif
                            </p>

                            @if ($canSetPic)
                                <input
                                    type="text"
                                    name="pic_name"
                                    value="{{ old('pic_name') }}"
                                    list="signer-names"
                                    maxlength="150"
                                    data-position-target="pic_position"
                                    class="signer-name w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                    placeholder="Nama PIC"
                                    autocomplete="off"
                                    required
                                >

                                <input
                                    type="text"
                                    name="pic_position"
                                    value="{{ old('pic_position') }}"
                                    maxlength="150"
                                    class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                    placeholder="Jabatan PIC (contoh: Staff K3L)"
                                >
                            @else
                                <div class="rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                                    <p class="font-semibold">{{ $user->name }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $user->position?->name ?? 'Jabatan belum diatur' }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-2">
                            <p class="text-[11px] font-semibold text-slate-700">Diketahui oleh (Atasan Langsung)</p>

                            <input
                                type="text"
                                name="superior_name"
                                value="{{ old('superior_name') }}"
                                list="signer-names"
                                maxlength="150"
                                data-position-target="superior_position"
                                class="signer-name w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                placeholder="Nama atasan langsung"
                                autocomplete="off"
                            >

                            <input
                                type="text"
                                name="superior_position"
                                value="{{ old('superior_position') }}"
                                maxlength="150"
                                class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                placeholder="Jabatan atasan langsung"
                            >
                        </div>
                    </div>
                </div>
                <div>
                    <label
                        for="note"
                        class="mb-1 block text-[11px] font-semibold text-slate-700"
                    >
                        Tujuan atau Catatan Pengajuan
                    </label>

                    <textarea
                        name="note"
                        id="note"
                        rows="3"
                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                        placeholder="Contoh: kebutuhan laptop untuk staff baru"
                    >{{ old('note') }}</textarea>
                </div>

                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="text-xs font-bold text-slate-800">
                            Daftar Barang Custom
                        </h3>

                        <p class="text-[10px] text-slate-500">
                            Minimal satu barang harus dimasukkan.
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

                <div
                    id="custom-items-wrapper"
                    class="space-y-3"
                >
                    @foreach ($oldItems as $index => $oldItem)
                        <div class="custom-item rounded-md border border-slate-200 bg-slate-50 p-3">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <p class="item-number text-[11px] font-bold text-slate-800">
                                        Barang {{ $index + 1 }}
                                    </p>

                                    <p class="text-[10px] text-slate-500">
                                        Isi data barang secara lengkap.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onclick="removeCustomItem(this)"
                                    class="rounded-md border border-red-200 bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700 hover:bg-red-100"
                                >
                                    Hapus
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Nama Barang
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="items[{{ $index }}][custom_item_name]"
                                        value="{{ $oldItem['custom_item_name'] ?? '' }}"
                                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        placeholder="Contoh: Laptop Lenovo ThinkPad"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Jenis Anggaran
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        name="items[{{ $index }}][budget_type]"
                                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        required
                                    >
                                        <option value="">-- Pilih --</option>
                                        <option value="OPEX" @selected(($oldItem['budget_type'] ?? '') === 'OPEX')>OPEX</option>
                                        <option value="CAPEX" @selected(($oldItem['budget_type'] ?? '') === 'CAPEX')>CAPEX</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Kategori / Kelompok
                                    </label>

                                    <input
                                        type="text"
                                        name="items[{{ $index }}][custom_category]"
                                        value="{{ $oldItem['custom_category'] ?? '' }}"
                                        list="category-suggestions"
                                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        placeholder="ATK, RTK, Obat P3K"
                                    >
                                </div>

                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Type / Merk
                                    </label>

                                    <input
                                        type="text"
                                        name="items[{{ $index }}][brand_type]"
                                        value="{{ $oldItem['brand_type'] ?? '' }}"
                                        maxlength="255"
                                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        placeholder="Contoh: Unicare 30L"
                                    >
                                </div>

                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Satuan
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="items[{{ $index }}][unit]"
                                        value="{{ $oldItem['unit'] ?? '' }}"
                                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        placeholder="unit, pcs, box"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Jumlah
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        name="items[{{ $index }}][quantity]"
                                        value="{{ $oldItem['quantity'] ?? 1 }}"
                                        min="1"
                                        class="quantity-input w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        oninput="calculateEstimatedTotal()"
                                        required
                                    >
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
                                        class="estimated-price-input w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        placeholder="7500000"
                                        oninput="calculateEstimatedTotal()"
                                    >
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Link Produk
                                    </label>

                                    <input
                                        type="url"
                                        name="items[{{ $index }}][product_url]"
                                        value="{{ $oldItem['product_url'] ?? '' }}"
                                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        placeholder="https://..."
                                    >
                                </div>

                                <div class="md:col-span-2 xl:col-span-4">
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Spesifikasi
                                    </label>

                                    <textarea
                                        name="items[{{ $index }}][specification]"
                                        rows="3"
                                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        placeholder="Contoh: RAM 16 GB, SSD 512 GB, layar 14 inci"
                                    >{{ $oldItem['specification'] ?? '' }}</textarea>
                                </div>

                                <div class="md:col-span-2 xl:col-span-4">
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Digunakan Untuk
                                    </label>

                                    <textarea
                                        name="items[{{ $index }}][usage_purpose]"
                                        rows="2"
                                        maxlength="1000"
                                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                        placeholder="Contoh: Sebagai fasilitas tanggap darurat pada area kerja lantai 2 dan 3"
                                    >{{ $oldItem['usage_purpose'] ?? '' }}</textarea>
                                </div>

                                <div class="md:col-span-2 xl:col-span-4">
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-700">
                                        Keterangan Tambahan
                                    </label>

                                    <textarea
                                        name="items[{{ $index }}][note]"
                                        rows="2"
                                        class="w-full rounded-md border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500"
                                    >{{ $oldItem['note'] ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <div class="w-full rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 sm:w-80">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                            Estimasi Total
                        </p>

                        <p
                            id="estimated-total"
                            class="mt-1 text-lg font-bold text-emerald-800"
                        >
                            Rp 0
                        </p>

                        <p class="text-[10px] text-emerald-700">
                            Total jumlah × harga per satuan.
                        </p>
                    </div>
                </div>
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
                    Simpan Pengajuan Custom
                </button>
            </div>
        </form>
    </div>

    <datalist id="signer-names">
        @foreach ($signerSuggestions as $person)
            <option value="{{ $person['name'] }}"></option>
        @endforeach
    </datalist>

    <script>
        // Memilih nama dari saran mengisi jabatannya otomatis (jika kolom jabatan masih kosong).
        (function () {
            const people = @json($signerSuggestions);

            document.querySelectorAll('.signer-name').forEach(function (input) {
                input.addEventListener('change', function () {
                    const person = people.find(function (candidate) {
                        return candidate.name.toLowerCase() === input.value.trim().toLowerCase();
                    });
                    const target = document.querySelector('[name="' + input.dataset.positionTarget + '"]');

                    if (person && target && target.value.trim() === '' && person.position) {
                        target.value = person.position;
                    }
                });
            });
        })();
    </script>
    <datalist id="category-suggestions">
        <option value="ATK"></option>
        <option value="RTK"></option>
        <option value="Obat P3K"></option>
    </datalist>

    <script>
        let customItemIndex = {{ count($oldItems) }};

        function addCustomItem() {
            const wrapper = document.getElementById(
                'custom-items-wrapper'
            );

            const firstItem = wrapper.querySelector(
                '.custom-item'
            );

            const newItem = firstItem.cloneNode(true);

            newItem.querySelectorAll(
                'input, textarea, select'
            ).forEach(function (element) {
                const oldName = element.getAttribute('name');

                if (oldName) {
                    element.setAttribute(
                        'name',
                        oldName.replace(
                            /\[\d+\]/,
                            '[' + customItemIndex + ']'
                        )
                    );
                }

                if (
                    element.type === 'number'
                    && oldName
                    && oldName.includes('[quantity]')
                ) {
                    element.value = 1;
                } else if (element.tagName === 'SELECT') {
                    element.selectedIndex = 0;
                } else {
                    element.value = '';
                }
            });

            wrapper.appendChild(newItem);

            customItemIndex++;

            refreshItemNumbers();
            calculateEstimatedTotal();
        }

        function removeCustomItem(button) {
            const items = document.querySelectorAll(
                '.custom-item'
            );

            if (items.length <= 1) {
                alert(
                    'Minimal harus ada satu barang dalam pengajuan.'
                );

                return;
            }

            button.closest('.custom-item').remove();

            refreshItemNumbers();
            calculateEstimatedTotal();
        }

        function refreshItemNumbers() {
            document.querySelectorAll(
                '.custom-item'
            ).forEach(function (item, index) {
                const number = item.querySelector(
                    '.item-number'
                );

                if (number) {
                    number.textContent =
                        'Barang ' + (index + 1);
                }
            });
        }

        function calculateEstimatedTotal() {
            let total = 0;

            document.querySelectorAll(
                '.custom-item'
            ).forEach(function (item) {
                const quantityInput = item.querySelector(
                    '.quantity-input'
                );

                const priceInput = item.querySelector(
                    '.estimated-price-input'
                );

                const quantity = parseFloat(
                    quantityInput?.value || 0
                );

                const price = parseFloat(
                    priceInput?.value || 0
                );

                total += quantity * price;
            });

            document.getElementById(
                'estimated-total'
            ).textContent = new Intl.NumberFormat(
                'id-ID',
                {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0,
                }
            ).format(total);
        }

        document.addEventListener(
            'DOMContentLoaded',
            function () {
                refreshItemNumbers();
                calculateEstimatedTotal();
            }
        );
    </script>
</x-app-layout>