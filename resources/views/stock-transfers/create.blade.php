<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold text-white">Buat Mutasi Stok</h1>
                <p class="text-[10px] text-white/80">
                    Pindahkan stok antar gudang dalam entitas yang sama.
                </p>
            </div>

            <a href="{{ route('stock-transfers.index') }}"
               class="rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white">
                Kembali
            </a>
        </div>
    </x-slot>

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
                            <label for="item_id"
                                   class="mb-1 block text-[11px] font-semibold text-slate-700">
                                Barang dari Gudang Asal <span class="text-red-500">*</span>
                            </label>

                            <select name="item_id" id="item_id" required
                                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                                <option value="">-- Pilih Barang --</option>
                                @foreach ($itemStocks as $stock)
                                    <option
                                        value="{{ $stock->item_id }}"
                                        data-entity="{{ $stock->entity_id }}"
                                        data-warehouse="{{ $stock->warehouse_id }}"
                                        data-stock="{{ $stock->current_stock }}"
                                        @selected((string) old('item_id') === (string) $stock->item_id)
                                    >
                                        {{ $stock->item?->code ?? '-' }}
                                        - {{ $stock->item?->name ?? '-' }}
                                        | {{ $stock->entity?->code ?? '-' }}
                                        / {{ $stock->warehouse?->name ?? '-' }}
                                        | Stok: {{ $stock->current_stock }}
                                    </option>
                                @endforeach
                            </select>

                            <p id="stock_information" class="mt-1 text-[10px] text-slate-500">
                                Pilih entitas dan gudang asal untuk menyaring barang.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label for="quantity"
                                       class="mb-1 block text-[11px] font-semibold text-slate-700">
                                    Jumlah Mutasi <span class="text-red-500">*</span>
                                </label>

                                <input type="number" name="quantity" id="quantity" min="1"
                                       value="{{ old('quantity') }}" required
                                       class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs"
                                       placeholder="Contoh: 10">
                            </div>

                            <div>
                                <label for="transfer_date"
                                       class="mb-1 block text-[11px] font-semibold text-slate-700">
                                    Tanggal Mutasi <span class="text-red-500">*</span>
                                </label>

                                <input type="date" name="transfer_date" id="transfer_date"
                                       value="{{ old('transfer_date', date('Y-m-d')) }}" required
                                       class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                            </div>
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
                            Setelah disimpan, stok gudang asal langsung berkurang dan stok gudang tujuan langsung bertambah. Transaksi tidak dapat diedit atau dihapus agar riwayat stok tetap aman.
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
            const itemSelect = document.getElementById('item_id');
            const stockInformation = document.getElementById('stock_information');
            const quantityInput = document.getElementById('quantity');

            const allOptions = Array.from(itemSelect.options).map(function (option) {
                return {
                    value: option.value,
                    text: option.text,
                    entity: option.dataset.entity || '',
                    warehouse: option.dataset.warehouse || '',
                    stock: option.dataset.stock || '',
                };
            });

            function filterItems() {
                const entityId = entitySelect.value;
                const sourceWarehouseId = sourceSelect.value;
                const previousValue = itemSelect.value;

                itemSelect.innerHTML = '';

                allOptions.forEach(function (item) {
                    if (item.value === '') {
                        itemSelect.appendChild(new Option(item.text, item.value));
                        return;
                    }

                    if (item.entity === entityId && item.warehouse === sourceWarehouseId) {
                        const option = new Option(item.text, item.value);
                        option.dataset.entity = item.entity;
                        option.dataset.warehouse = item.warehouse;
                        option.dataset.stock = item.stock;
                        option.selected = item.value === previousValue;
                        itemSelect.appendChild(option);
                    }
                });

                updateStockInfo();
            }

            function updateStockInfo() {
                const selected = itemSelect.options[itemSelect.selectedIndex];

                if (! selected || ! selected.value) {
                    stockInformation.textContent = 'Pilih barang untuk melihat stok tersedia.';
                    quantityInput.removeAttribute('max');
                    return;
                }

                const stock = selected.dataset.stock || '0';
                stockInformation.textContent = 'Stok tersedia pada gudang asal: ' + stock;
                quantityInput.max = stock;
            }

            function preventSameWarehouse() {
                Array.from(destinationSelect.options).forEach(function (option) {
                    option.disabled = option.value !== '' && option.value === sourceSelect.value;
                });

                if (destinationSelect.value && destinationSelect.value === sourceSelect.value) {
                    destinationSelect.value = '';
                }
            }

            entitySelect.addEventListener('change', filterItems);
            sourceSelect.addEventListener('change', function () {
                filterItems();
                preventSameWarehouse();
            });
            itemSelect.addEventListener('change', updateStockInfo);

            filterItems();
            preventSameWarehouse();
        });
    </script>
</x-app-layout>
