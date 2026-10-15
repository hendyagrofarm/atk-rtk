<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold text-white">
                    Buat Pengajuan ATK/RTK
                </h1>

                <p class="text-[10px] text-white/80">
                    Pilih entitas dan gudang sumber stok.
                </p>
            </div>

            <a href="{{ route('staff.requests.index') }}"
               class="rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white">
                Kembali
            </a>
        </div>
    </x-slot>

    @php
        $oldItems = old('items', [
            ['item_id' => '', 'quantity' => '', 'note' => '']
        ]);
    @endphp

    <div class="space-y-3">
        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-700">
                <ul class="list-inside list-disc text-[11px]">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold text-slate-800">
                    Form Pengajuan Barang
                </h2>

                <p class="text-[11px] text-slate-500">
                    Stok mengikuti kombinasi entitas dan gudang yang dipilih.
                </p>
            </div>

            <form action="{{ route('staff.requests.store') }}" method="POST">
                @csrf

                <div class="space-y-4 px-4 py-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                Entitas *
                            </label>

                            <select name="entity_id"
                                    id="entity_id"
                                    onchange="refreshAllStocks()"
                                    required
                                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                                <option value="">-- Pilih Entitas --</option>

                                @foreach ($entities as $entity)
                                    <option value="{{ $entity->id }}"
                                            @selected(
                                                (string) old(
                                                    'entity_id',
                                                    $user->entity_id
                                                ) === (string) $entity->id
                                            )>
                                        {{ $entity->code }} - {{ $entity->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                Gudang Sumber *
                            </label>

                            <select name="warehouse_id"
                                    id="warehouse_id"
                                    onchange="refreshAllStocks()"
                                    required
                                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                                <option value="">-- Pilih Gudang --</option>

                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}"
                                            @selected(
                                                (string) old(
                                                    'warehouse_id',
                                                    $user->warehouse_id
                                                ) === (string) $warehouse->id
                                            )>
                                        {{ $warehouse->code }} - {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                            Catatan Pengajuan
                        </label>

                        <textarea name="note"
                                  rows="3"
                                  maxlength="1000"
                                  class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs"
                                  placeholder="Kebutuhan operasional">{{ old('note') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">
                                Daftar Barang
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Minimal satu barang.
                            </p>
                        </div>

                        <button type="button"
                                onclick="addItemRow()"
                                class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white">
                            Tambah Barang
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-md border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="w-2/5 px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Barang</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Stok Lokasi</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Jumlah</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Keterangan</th>
                                    <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="items-wrapper" class="divide-y divide-slate-100">
                                @foreach ($oldItems as $index => $oldItem)
                                    <tr class="item-row">
                                        <td class="px-4 py-2.5">
                                            <input type="search"
                                                   class="item-search mb-1.5 block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs"
                                                   placeholder="Cari kode / nama barang..."
                                                   autocomplete="off"
                                                   oninput="filterItems(this)"
                                                   onkeydown="searchKeydown(event, this)">

                                            <select name="items[{{ $index }}][item_id]"
                                                    class="item-select block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs"
                                                    onchange="updateStock(this)"
                                                    required>
                                                <option value="">-- Pilih Barang --</option>

                                                @foreach ($items as $item)
                                                    @php
                                                        $stocks = $item->itemStocks->mapWithKeys(function ($stock) {
                                                            $key = $stock->entity_id . '-' . $stock->warehouse_id;

                                                            return [
                                                                $key => [
                                                                    'stock' => $stock->current_stock,
                                                                    'minimum' => $stock->minimum_stock,
                                                                ],
                                                            ];
                                                        });
                                                    @endphp

                                                    <option value="{{ $item->id }}"
                                                            data-unit="{{ $item->unit }}"
                                                            data-stocks='@json($stocks)'
                                                            @selected(
                                                                (string) ($oldItem['item_id'] ?? '')
                                                                === (string) $item->id
                                                            )>
                                                        {{ $item->code }} - {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-2.5">
                                            <span class="stock-text rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                                -
                                            </span>

                                            <p class="shortage-warning mt-1 max-w-[220px] whitespace-normal text-[10px] font-semibold leading-4 text-amber-700"></p>
                                        </td>

                                        <td class="px-4 py-2.5">
                                            <input type="number"
                                                   name="items[{{ $index }}][quantity]"
                                                   min="1"
                                                   value="{{ $oldItem['quantity'] ?? '' }}"
                                                   oninput="updateStock(this.closest('tr').querySelector('.item-select'))"
                                                   required
                                                   class="w-24 rounded-md border-slate-300 px-3 py-1.5 text-xs">
                                        </td>

                                        <td class="px-4 py-2.5">
                                            <input type="text"
                                                   name="items[{{ $index }}][note]"
                                                   value="{{ $oldItem['note'] ?? '' }}"
                                                   class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                                        </td>

                                        <td class="px-4 py-2.5 text-center">
                                            <button type="button"
                                                    onclick="removeItemRow(this)"
                                                    class="rounded-md bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-md border border-cyan-100 bg-cyan-50 px-3 py-2 text-[11px] text-cyan-800">
                        Jumlah pengajuan boleh melebihi stok. Kekurangan stok dicatat untuk laporan, dan approver hanya dapat menyetujui sebesar stok yang tersedia.
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <a href="{{ route('staff.requests.index') }}"
                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                        Batal
                    </a>

                    <button type="submit"
                            class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white">
                        Simpan Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let itemIndex = {{ count($oldItems) }};

        function selectedLocationKey() {
            const entityId = document.getElementById('entity_id').value;
            const warehouseId = document.getElementById('warehouse_id').value;

            if (!entityId || !warehouseId) {
                return null;
            }

            return entityId + '-' + warehouseId;
        }

        function updateStock(select) {
            const key = selectedLocationKey();
            const option = select.options[select.selectedIndex];
            const row = select.closest('tr');
            const stockText = row.querySelector('.stock-text');
            const quantityInput = row.querySelector('input[type="number"]');
            const warning = row.querySelector('.shortage-warning');

            // Jumlah tidak dibatasi stok: kekurangan tetap bisa diajukan dan dicatat.
            quantityInput.removeAttribute('max');
            warning.textContent = '';

            if (!key) {
                stockText.textContent = 'Pilih lokasi';
                stockText.className = 'stock-text rounded-md bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-700';
                return;
            }

            if (!option || !option.value) {
                stockText.textContent = '-';
                stockText.className = 'stock-text rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700';
                return;
            }

            const stocks = JSON.parse(option.getAttribute('data-stocks') || '{}');
            const locationStock = stocks[key];
            const unit = option.getAttribute('data-unit') || '';
            const requested = Number(quantityInput.value || 0);

            const stock = locationStock ? Number(locationStock.stock || 0) : 0;
            const minimum = locationStock ? Number(locationStock.minimum || 0) : 0;

            if (!locationStock || stock <= 0) {
                stockText.textContent = 'Stok kosong';
                stockText.className = 'stock-text rounded-md bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700';

                if (requested > 0) {
                    warning.textContent = 'Stok kosong. ' + requested + ' ' + unit + ' dicatat sebagai kekurangan.';
                }

                return;
            }

            stockText.textContent = stock + ' ' + unit + ' | Min: ' + minimum;
            stockText.className = stock <= minimum
                ? 'stock-text rounded-md bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700'
                : 'stock-text rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700';

            if (requested > stock) {
                warning.textContent = 'Stok hanya ' + stock + ' ' + unit + '. Kekurangan ' + (requested - stock) + ' ' + unit + ' dicatat.';
            }
        }

        /*
         * Pencarian barang: daftar opsi asli disimpan sekali, lalu select
         * dibangun ulang sesuai kata kunci. Opsi yang sedang dipilih selalu
         * dipertahankan agar pilihan tidak hilang saat mengetik.
         */
        let masterOptions = [];

        function initItemSearch() {
            const firstSelect = document.querySelector('.item-select');

            masterOptions = Array.from(firstSelect.options).map(function (option) {
                return option.cloneNode(true);
            });
        }

        function filterItems(input) {
            const select = input.closest('td').querySelector('.item-select');
            const keyword = input.value.trim().toLowerCase();
            const selectedValue = select.value;
            let matches = 0;

            select.innerHTML = '';

            masterOptions.forEach(function (option) {
                const isPlaceholder = option.value === '';
                const isSelected = option.value !== '' && option.value === selectedValue;
                const matched = keyword === '' || option.textContent.toLowerCase().includes(keyword);

                if (isPlaceholder || isSelected || matched) {
                    const copy = option.cloneNode(true);
                    copy.selected = isSelected;
                    select.appendChild(copy);

                    if (!isPlaceholder && matched) {
                        matches++;
                    }
                }
            });

            select.options[0].textContent = keyword === ''
                ? '-- Pilih Barang --'
                : (matches === 0 ? '-- Barang tidak ditemukan --' : '-- ' + matches + ' barang ditemukan --');

            updateStock(select);
        }

        function searchKeydown(event, input) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();

            const select = input.closest('td').querySelector('.item-select');
            const candidates = Array.from(select.options).filter(function (option) {
                return option.value !== '';
            });

            if (candidates.length >= 1) {
                select.value = candidates[0].value;
                updateStock(select);
                input.closest('tr').querySelector('input[type="number"]').focus();
            }
        }

        function refreshAllStocks() {
            document.querySelectorAll('.item-select').forEach(updateStock);
        }

        function addItemRow() {
            const wrapper = document.getElementById('items-wrapper');
            const firstRow = wrapper.querySelector('.item-row');
            const newRow = firstRow.cloneNode(true);

            newRow.querySelectorAll('select, input').forEach(function (element) {
                const name = element.getAttribute('name');

                if (name) {
                    element.setAttribute(
                        'name',
                        name.replace(/\[\d+\]/, '[' + itemIndex + ']')
                    );
                }

                if (element.tagName === 'SELECT') {
                    element.innerHTML = '';
                    masterOptions.forEach(function (option) {
                        element.appendChild(option.cloneNode(true));
                    });
                    element.selectedIndex = 0;
                } else {
                    element.value = '';
                    element.removeAttribute('max');
                }
            });

            wrapper.appendChild(newRow);
            itemIndex++;
            refreshAllStocks();
        }

        function removeItemRow(button) {
            const rows = document.querySelectorAll('.item-row');

            if (rows.length <= 1) {
                alert('Minimal harus ada satu barang.');
                return;
            }

            button.closest('tr').remove();
        }

        document.addEventListener('DOMContentLoaded', function () {
            initItemSearch();
            refreshAllStocks();
        });
    </script>
</x-app-layout>
