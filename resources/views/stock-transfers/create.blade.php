<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold text-white">Buat Mutasi Stok</h1>
                <p class="text-[10px] text-white/80">
                    Pindahkan satu atau beberapa barang antar gudang dalam entitas yang sama.
                </p>
            </div>

            <a href="{{ route('stock-transfers.index') }}"
               class="rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white">
                Kembali
            </a>
        </div>
    </x-slot>

    @php
        $stockOptions = $itemStocks->map(fn ($stock) => [
            'value' => (string) $stock->item_id,
            'text' => ($stock->item?->code ?? '-') . ' - ' . ($stock->item?->name ?? '-'),
            'entity' => (string) $stock->entity_id,
            'warehouse' => (string) $stock->warehouse_id,
            'stock' => (int) $stock->current_stock,
            'unit' => $stock->item?->unit ?? '',
        ])->values();

        $oldRows = old('items', [['item_id' => '', 'quantity' => '']]);
    @endphp

    <div class="mx-auto max-w-3xl">
        <div class="space-y-3">
            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2">
                    <p class="text-[11px] font-semibold text-red-700">Periksa kembali data berikut:</p>
                    <ul class="mt-1 list-inside list-disc text-[10px] text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <form method="POST" action="{{ route('stock-transfers.store') }}">
                    @csrf

                    <div class="space-y-4 p-4">
                        <div>
                            <label for="entity_id"
                                   class="mb-1 block text-[11px] font-semibold text-slate-700">
                                Entitas <span class="text-red-500">*</span>
                            </label>

                            <select name="entity_id" id="entity_id" required
                                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                                <option value="">-- Pilih Entitas --</option>
                                @foreach ($entities as $entity)
                                    <option value="{{ $entity->id }}"
                                            @selected((string) old('entity_id', $user->entity_id) === (string) $entity->id)>
                                        {{ $entity->code }} - {{ $entity->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label for="source_warehouse_id"
                                       class="mb-1 block text-[11px] font-semibold text-slate-700">
                                    Gudang Asal <span class="text-red-500">*</span>
                                </label>

                                <select name="source_warehouse_id" id="source_warehouse_id" required
                                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                                    <option value="">-- Pilih Gudang Asal --</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}"
                                                @selected((string) old('source_warehouse_id') === (string) $warehouse->id)>
                                            {{ $warehouse->code }} - {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="destination_warehouse_id"
                                       class="mb-1 block text-[11px] font-semibold text-slate-700">
                                    Gudang Tujuan <span class="text-red-500">*</span>
                                </label>

                                <select name="destination_warehouse_id" id="destination_warehouse_id" required
                                        class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                                    <option value="">-- Pilih Gudang Tujuan --</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}"
                                                @selected((string) old('destination_warehouse_id') === (string) $warehouse->id)>
                                            {{ $warehouse->code }} - {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <label class="block text-[11px] font-semibold text-slate-700">
                                    Barang dari Gudang Asal <span class="text-red-500">*</span>
                                </label>

                                <button type="button" id="add_row"
                                        class="rounded-md bg-emerald-600 px-2.5 py-1 text-[10px] font-semibold text-white hover:bg-emerald-700">
                                    + Tambah Barang
                                </button>
                            </div>

                            <p id="stock_information" class="mb-2 text-[10px] text-slate-500">
                                Pilih entitas dan gudang asal untuk menampilkan barang.
                            </p>

                            <div id="rows" class="space-y-2"></div>
                        </div>

                        <div>
                            <label for="transfer_date"
                                   class="mb-1 block text-[11px] font-semibold text-slate-700">
                                Tanggal Mutasi <span class="text-red-500">*</span>
                            </label>

                            <input type="date" name="transfer_date" id="transfer_date"
                                   value="{{ old('transfer_date', date('Y-m-d')) }}" required
                                   class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs md:w-1/2">
                        </div>

                        <div>
                            <label for="note"
                                   class="mb-1 block text-[11px] font-semibold text-slate-700">
                                Keterangan
                            </label>

                            <textarea name="note" id="note" rows="3" maxlength="2000"
                                      class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs"
                                      placeholder="Alasan atau catatan mutasi">{{ old('note') }}</textarea>
                        </div>

                        <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[10px] leading-4 text-amber-700">
                            Setelah disimpan, stok gudang asal langsung berkurang dan stok gudang tujuan langsung bertambah. Setiap barang dicatat sebagai satu transaksi mutasi sendiri. Jika salah satu barang gagal, seluruh mutasi dibatalkan. Transaksi tidak dapat diedit atau dihapus agar riwayat stok tetap aman.
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                        <a href="{{ route('stock-transfers.index') }}"
                           class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                            Batal
                        </a>

                        <button type="submit"
                                class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                            Simpan Mutasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const entitySelect = document.getElementById('entity_id');
            const sourceSelect = document.getElementById('source_warehouse_id');
            const destinationSelect = document.getElementById('destination_warehouse_id');
            const stockInformation = document.getElementById('stock_information');
            const rowsWrapper = document.getElementById('rows');

            const stockOptions = @json($stockOptions);
            const initialRows = @json(array_values($oldRows));
            let rowIndex = 0;

            const inputClass = 'block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs';

            function availableItems() {
                return stockOptions.filter(function (item) {
                    return item.entity === entitySelect.value
                        && item.warehouse === sourceSelect.value;
                });
            }

            // Barang yang sudah dipilih di baris lain tidak ditawarkan lagi.
            function chosenIds(exceptRow) {
                return Array.from(rowsWrapper.querySelectorAll('.item-select'))
                    .filter(function (select) { return select.closest('.transfer-row') !== exceptRow; })
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

                availableItems().forEach(function (item) {
                    const isCurrent = item.value === current;
                    const matched = keyword === '' || item.text.toLowerCase().includes(keyword);

                    if (taken.includes(item.value) || (! matched && ! isCurrent)) {
                        return;
                    }

                    const option = new Option(item.text + ' | Stok: ' + item.stock, item.value);
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

                updateRow(row);
            }

            function updateRow(row) {
                const select = row.querySelector('.item-select');
                const quantity = row.querySelector('.qty');
                const info = row.querySelector('.row-info');
                const item = availableItems().find(function (candidate) {
                    return candidate.value === select.value;
                });

                if (! item) {
                    info.textContent = '';
                    quantity.removeAttribute('max');
                    return;
                }

                info.textContent = 'Stok tersedia: ' + item.stock + ' ' + item.unit;
                quantity.max = item.stock;
            }

            function refreshAll() {
                const hasLocation = entitySelect.value && sourceSelect.value;
                const count = availableItems().length;

                stockInformation.textContent = ! hasLocation
                    ? 'Pilih entitas dan gudang asal untuk menampilkan barang.'
                    : (count === 0
                        ? 'Tidak ada barang bersetok pada gudang asal ini.'
                        : count + ' barang tersedia pada gudang asal.');

                rowsWrapper.querySelectorAll('.transfer-row').forEach(fillSelect);
            }

            function addRow(data) {
                const index = rowIndex++;
                const row = document.createElement('div');
                row.className = 'transfer-row rounded-md border border-slate-200 bg-slate-50 p-2';
                row.innerHTML =
                    '<div class="grid grid-cols-1 gap-2 md:grid-cols-12">' +
                        '<div class="md:col-span-7">' +
                            '<input type="search" class="item-search mb-1.5 ' + inputClass + '" placeholder="Cari kode / nama barang..." autocomplete="off">' +
                            '<select name="items[' + index + '][item_id]" class="item-select ' + inputClass + '" required></select>' +
                            '<p class="row-info mt-1 text-[10px] text-slate-500"></p>' +
                        '</div>' +
                        '<div class="md:col-span-3">' +
                            '<input type="number" name="items[' + index + '][quantity]" class="qty ' + inputClass + '" min="1" required placeholder="Jumlah">' +
                        '</div>' +
                        '<div class="md:col-span-2 md:text-right">' +
                            '<button type="button" class="remove-row rounded-md bg-red-50 px-2 py-1.5 text-[10px] font-semibold text-red-700">Hapus</button>' +
                        '</div>' +
                    '</div>';

                rowsWrapper.appendChild(row);

                const select = row.querySelector('.item-select');
                const search = row.querySelector('.item-search');
                const quantity = row.querySelector('.qty');

                if (data && data.item_id) {
                    select.appendChild(new Option('', data.item_id, true, true));
                }
                if (data && data.quantity) {
                    quantity.value = data.quantity;
                }

                fillSelect(row);

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
                    if (rowsWrapper.querySelectorAll('.transfer-row').length <= 1) {
                        alert('Minimal harus ada satu barang.');
                        return;
                    }
                    row.remove();
                    refreshAll();
                });
            }

            function preventSameWarehouse() {
                Array.from(destinationSelect.options).forEach(function (option) {
                    option.disabled = option.value !== '' && option.value === sourceSelect.value;
                });

                if (destinationSelect.value && destinationSelect.value === sourceSelect.value) {
                    destinationSelect.value = '';
                }
            }

            function resetRowsOnLocationChange() {
                rowsWrapper.querySelectorAll('.item-select').forEach(function (select) { select.value = ''; });
                refreshAll();
            }

            entitySelect.addEventListener('change', resetRowsOnLocationChange);
            sourceSelect.addEventListener('change', function () {
                resetRowsOnLocationChange();
                preventSameWarehouse();
            });
            document.getElementById('add_row').addEventListener('click', function () {
                addRow();
                rowsWrapper.lastElementChild.querySelector('.item-search').focus();
            });

            initialRows.forEach(addRow);
            refreshAll();
            preventSameWarehouse();
        });
    </script>
</x-app-layout>
