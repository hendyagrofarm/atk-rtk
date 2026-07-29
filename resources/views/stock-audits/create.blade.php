<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold text-white">
                Buat Audit Stok
            </h1>
            <p class="text-[10px] text-white/80">
                Masukkan hasil stok fisik untuk menyesuaikan stok sistem.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                    <ul class="list-disc pl-5 text-xs text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('stock-audits.store') }}"
                  class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Tanggal Audit
                        </label>
                        <input type="date"
                               name="audit_date"
                               value="{{ old('audit_date', now()->format('Y-m-d')) }}"
                               required
                               class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Lokasi dan Barang
                        </label>
                        <select id="item_stock_selector"
                                class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs"
                                required>
                            <option value="">Pilih lokasi dan barang</option>
                            @foreach ($itemStocks as $stock)
                                <option
                                    value="{{ $stock->id }}"
                                    data-entity-id="{{ $stock->entity_id }}"
                                    data-warehouse-id="{{ $stock->warehouse_id }}"
                                    data-item-id="{{ $stock->item_id }}"
                                    data-system-stock="{{ $stock->current_stock }}"
                                >
                                    {{ $stock->entity?->code ?? '-' }}
                                    |
                                    {{ $stock->warehouse?->name ?? '-' }}
                                    |
                                    {{ $stock->item?->code ?? '-' }}
                                    -
                                    {{ $stock->item?->name ?? '-' }}
                                    |
                                    Stok: {{ $stock->current_stock }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <input type="hidden" name="entity_id" id="entity_id" value="{{ old('entity_id') }}">
                <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ old('warehouse_id') }}">
                <input type="hidden" name="item_id" id="item_id" value="{{ old('item_id') }}">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Stok Sistem
                        </label>
                        <input type="number"
                               id="system_stock"
                               readonly
                               class="block w-full rounded-md border-slate-300 bg-slate-100 px-3 py-2 text-xs">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Stok Fisik
                        </label>
                        <input type="number"
                               name="physical_stock"
                               id="physical_stock"
                               min="0"
                               value="{{ old('physical_stock') }}"
                               required
                               class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">
                            Selisih
                        </label>
                        <input type="number"
                               id="difference"
                               readonly
                               class="block w-full rounded-md border-slate-300 bg-slate-100 px-3 py-2 text-xs font-bold">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold text-slate-700">
                        Alasan Penyesuaian
                    </label>
                    <select name="reason"
                            required
                            class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs">
                        <option value="">Pilih alasan</option>
                        @foreach ([
                            'Hasil stock opname',
                            'Selisih pencatatan',
                            'Barang rusak',
                            'Barang hilang',
                            'Kesalahan input',
                            'Penyesuaian administrasi',
                            'Lainnya'
                        ] as $reason)
                            <option value="{{ $reason }}"
                                    @selected(old('reason') === $reason)>
                                {{ $reason }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold text-slate-700">
                        Keterangan
                    </label>
                    <textarea name="note"
                              rows="3"
                              placeholder="Tambahkan penjelasan jika diperlukan"
                              class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs">{{ old('note') }}</textarea>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-700">
                    Setelah disimpan, stok sistem langsung diubah mengikuti stok fisik.
                    Riwayat audit tidak dapat dihapus dari aplikasi.
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('stock-audits.index') }}"
                       class="rounded-md border border-slate-300 bg-white px-4 py-2 text-[11px] font-semibold text-slate-700">
                        Batal
                    </a>

                    <button type="submit"
                            class="rounded-md bg-emerald-700 px-4 py-2 text-[11px] font-semibold text-white">
                        Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const selector = document.getElementById('item_stock_selector');
        const systemStock = document.getElementById('system_stock');
        const physicalStock = document.getElementById('physical_stock');
        const difference = document.getElementById('difference');

        function updateSelectedStock() {
            const option = selector.options[selector.selectedIndex];

            if (! option || ! option.value) {
                document.getElementById('entity_id').value = '';
                document.getElementById('warehouse_id').value = '';
                document.getElementById('item_id').value = '';
                systemStock.value = '';
                difference.value = '';
                return;
            }

            document.getElementById('entity_id').value =
                option.dataset.entityId;

            document.getElementById('warehouse_id').value =
                option.dataset.warehouseId;

            document.getElementById('item_id').value =
                option.dataset.itemId;

            systemStock.value = option.dataset.systemStock;
            calculateDifference();
        }

        function calculateDifference() {
            if (
                systemStock.value === ''
                || physicalStock.value === ''
            ) {
                difference.value = '';
                return;
            }

            difference.value =
                Number(physicalStock.value)
                - Number(systemStock.value);
        }

        selector.addEventListener('change', updateSelectedStock);
        physicalStock.addEventListener('input', calculateDifference);
    </script>
</x-app-layout>
