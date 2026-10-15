<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-white">
                Tambah Stok Masuk
            </h1>

            <p class="text-[10px] leading-4 text-white/80">
                Catat penambahan stok satu atau beberapa barang sekaligus.
            </p>
        </div>
    </x-slot>

    @php
        $itemOptions = $items->map(fn ($item) => [
            'value' => (string) $item->id,
            'text' => $item->code . ' - ' . $item->name
                . ($item->category ? ' | ' . $item->category->name : ''),
            'unit' => $item->unit ?? '',
        ])->values();

        $oldRows = old('items', [['item_id' => '', 'quantity' => '']]);
    @endphp

    <div class="mx-auto max-w-3xl">
        <div class="space-y-3">
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-red-800 shadow-sm">
                    <p class="text-xs font-semibold">Terjadi Kesalahan</p>
                    <p class="text-[11px]">{{ session('error') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-red-800 shadow-sm">
                    <p class="text-xs font-semibold">Data belum lengkap:</p>

                    <ul class="mt-1 list-inside list-disc text-[10px] leading-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h2 class="text-xs font-semibold text-slate-800">
                        Form Tambah Stok Masuk
                    </h2>

                    <p class="text-[10px] text-slate-500">
                        Pilihan lokasi dibatasi sesuai hak akses akun.
                    </p>
                </div>

                <form method="POST" action="{{ route('stock-ins.store') }}">
                    @csrf

                    <div class="space-y-3 px-4 py-4">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label for="entity_id"
                                       class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                    Entitas
                                    <span class="text-red-500">*</span>
                                </label>

                                <select name="entity_id"
                                        id="entity_id"
                                        required
                                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Pilih Entitas --</option>

                                    @foreach ($entities as $entity)
                                        <option value="{{ $entity->id }}"
                                                @selected(
                                                    (string) old(
                                                        'entity_id',
                                                        $currentUser->entity_id
                                                    ) === (string) $entity->id
                                                )>
                                            {{ $entity->code }} - {{ $entity->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="warehouse_id"
                                       class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                    Gudang Tujuan
                                    <span class="text-red-500">*</span>
                                </label>

                                <select name="warehouse_id"
                                        id="warehouse_id"
                                        required
                                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Pilih Gudang --</option>

                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}"
                                                @selected(
                                                    (string) old(
                                                        'warehouse_id',
                                                        $currentUser->warehouse_id
                                                    ) === (string) $warehouse->id
                                                )>
                                            {{ $warehouse->code }} - {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if (! $isSuperAdmin)
                            <div class="rounded-md border border-sky-200 bg-sky-50 px-3 py-2">
                                <p class="text-[10px] font-semibold text-sky-800">
                                    Hak akses akun
                                </p>

                                <p class="mt-0.5 text-[10px] leading-4 text-sky-700">
                                    Tersedia {{ $entities->count() }} entitas dan
                                    {{ $warehouses->count() }} gudang yang boleh dipilih.
                                </p>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label for="date"
                                       class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                    Tanggal Masuk
                                    <span class="text-red-500">*</span>
                                </label>

                                <input type="date"
                                       name="date"
                                       id="date"
                                       value="{{ old('date', date('Y-m-d')) }}"
                                       required
                                       class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm">
                            </div>

                            <div>
                                <label for="supplier"
                                       class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                    Supplier
                                </label>

                                <input type="text"
                                       name="supplier"
                                       id="supplier"
                                       value="{{ old('supplier') }}"
                                       maxlength="255"
                                       class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm"
                                       placeholder="Contoh: Toko ATK Maju">
                            </div>
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label class="block text-[11px] font-semibold text-slate-700">
                                    Daftar Barang
                                    <span class="text-red-500">*</span>
                                </label>

                                <button type="button" id="add_row"
                                        class="rounded-md bg-emerald-600 px-2.5 py-1 text-[10px] font-semibold text-white hover:bg-emerald-700">
                                    + Tambah Barang
                                </button>
                            </div>

                            <div id="rows" class="space-y-2"></div>

                            <p id="row_count" class="mt-1.5 text-[10px] text-slate-500"></p>
                        </div>

                        <div>
                            <label for="note"
                                   class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                Keterangan
                            </label>

                            <textarea name="note"
                                      id="note"
                                      rows="3"
                                      maxlength="2000"
                                      class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm"
                                      placeholder="Catatan tambahan jika ada">{{ old('note') }}</textarea>
                        </div>

                        <div class="rounded-md border border-indigo-100 bg-indigo-50 px-3 py-2 text-[11px] text-indigo-700">
                            Stok akan bertambah pada kombinasi entitas, gudang, dan barang yang dipilih. Setiap barang dicatat sebagai satu transaksi stok masuk. Jika salah satu gagal, seluruhnya dibatalkan.
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                        <a href="{{ route('stock-ins.index') }}"
                           class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                            Batal
                        </a>

                        <button type="submit"
                                class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                            Simpan Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const itemOptions = @json($itemOptions);
            const initialRows = Object.entries(@json($oldRows));
            const rowsWrapper = document.getElementById('rows');
            const rowCount = document.getElementById('row_count');
            const fieldClass = 'block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
            let rowIndex = 0;

            // Barang yang sudah dipilih di baris lain tidak ditawarkan lagi.
            function chosenIds(exceptRow) {
                return Array.from(rowsWrapper.querySelectorAll('.item-select'))
                    .filter(function (select) { return select.closest('.stock-row') !== exceptRow; })
                    .map(function (select) { return select.value; })
                    .filter(Boolean);
            }

            function fillSelect(row) {
                const select = row.querySelector('.item-select');
                const keyword = row.querySelector('.item-search').value.trim().toLowerCase();
                const current = select.value;
                const taken = chosenIds(row);
                let matches = 0;

                select.innerHTML = '';
                select.appendChild(new Option('-- Pilih Barang --', ''));

                itemOptions.forEach(function (item) {
                    const isCurrent = item.value === current;
                    const matched = keyword === '' || item.text.toLowerCase().includes(keyword);

                    if (taken.includes(item.value) || (! matched && ! isCurrent)) {
                        return;
                    }

                    const option = new Option(item.text, item.value);
                    option.selected = isCurrent;
                    select.appendChild(option);

                    if (matched) {
                        matches++;
                    }
                });

                if (keyword !== '') {
                    select.options[0].textContent = matches === 0
                        ? '-- Barang tidak ditemukan --'
                        : '-- ' + matches + ' barang ditemukan --';
                }

                const chosen = itemOptions.find(function (item) { return item.value === select.value; });
                row.querySelector('.unit-text').textContent = chosen ? chosen.unit : '';
            }

            function refreshAll() {
                const rows = rowsWrapper.querySelectorAll('.stock-row');

                rows.forEach(fillSelect);
                rowCount.textContent = rows.length + ' barang akan ditambahkan stoknya';
            }

            function addRow(data, key) {
                data = data || {};
                const index = key !== undefined ? Number(key) : rowIndex;
                rowIndex = Math.max(rowIndex, index + 1);

                const row = document.createElement('div');
                row.className = 'stock-row rounded-md border border-slate-200 bg-slate-50 p-2';
                row.innerHTML =
                    '<div class="grid grid-cols-1 gap-2 md:grid-cols-12">' +
                        '<div class="md:col-span-7">' +
                            '<input type="search" class="item-search mb-1.5 ' + fieldClass + '" placeholder="Cari kode / nama barang..." autocomplete="off">' +
                            '<select name="items[' + index + '][item_id]" class="item-select ' + fieldClass + '" required></select>' +
                        '</div>' +
                        '<div class="md:col-span-3">' +
                            '<input type="number" name="items[' + index + '][quantity]" class="qty ' + fieldClass + '" min="1" required placeholder="Jumlah">' +
                            '<p class="unit-text mt-1 text-[10px] text-slate-500"></p>' +
                        '</div>' +
                        '<div class="md:col-span-2 md:text-right">' +
                            '<button type="button" class="remove-row rounded-md bg-red-50 px-2 py-1.5 text-[10px] font-semibold text-red-700">Hapus</button>' +
                        '</div>' +
                    '</div>';

                rowsWrapper.appendChild(row);

                const select = row.querySelector('.item-select');
                const search = row.querySelector('.item-search');
                const quantity = row.querySelector('.qty');

                if (data.item_id) {
                    select.appendChild(new Option('', data.item_id, true, true));
                }
                if (data.quantity) {
                    quantity.value = data.quantity;
                }

                search.addEventListener('input', function () { fillSelect(row); });
                search.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') { return; }
                    event.preventDefault();

                    const first = Array.from(select.options).find(function (option) { return option.value !== ''; });
                    if (first) {
                        select.value = first.value;
                        refreshAll();
                        quantity.focus();
                    }
                });
                select.addEventListener('change', refreshAll);
                row.querySelector('.remove-row').addEventListener('click', function () {
                    if (rowsWrapper.querySelectorAll('.stock-row').length <= 1) {
                        alert('Minimal harus ada satu barang.');
                        return;
                    }
                    row.remove();
                    refreshAll();
                });

                refreshAll();

                return row;
            }

            document.getElementById('add_row').addEventListener('click', function () {
                const row = addRow();
                row.querySelector('.item-search').focus();
            });

            initialRows.forEach(function (entry) { addRow(entry[1], entry[0]); });
        });
    </script>
</x-app-layout>
