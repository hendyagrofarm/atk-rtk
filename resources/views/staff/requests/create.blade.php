<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold leading-5 text-white">
                    Buat Pengajuan ATK/RTK
                </h1>
                <p class="text-[10px] leading-4 text-white/80">
                    Buat permintaan barang ATK/RTK berdasarkan entitas ANR/MAA.
                </p>
            </div>

            <a href="{{ route('staff.requests.index') }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-white/20">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
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
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-800 shadow-sm">
                <p class="text-xs font-semibold">Terjadi Kesalahan</p>
                <p class="text-[11px]">{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-800 shadow-sm">
                <p class="text-xs font-semibold">Terjadi kesalahan:</p>
                <ul class="mt-1 list-inside list-disc text-[11px]">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold leading-5 text-slate-800">
                    Form Pengajuan Barang
                </h2>
                <p class="text-[11px] leading-4 text-slate-500">
                    Pilih entitas terlebih dahulu, lalu pilih barang dan jumlah yang dibutuhkan.
                </p>
            </div>

            <form action="{{ route('staff.requests.store') }}" method="POST">
                @csrf

                <div class="space-y-4 px-4 py-4">
                    {{-- Entitas --}}
                    <div>
                        <label for="entity_id" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                            Entitas <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="entity_id"
                            id="entity_id"
                            onchange="refreshAllStocks()"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-[#3B8DBD] focus:ring-[#3B8DBD]"
                        >
                            <option value="">-- Pilih Entitas --</option>
                            @foreach ($entities as $entity)
                                <option value="{{ $entity->id }}" @selected(old('entity_id') == $entity->id)>
                                    {{ $entity->code }} - {{ $entity->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('entity_id')
                            <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label for="note" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                            Catatan Pengajuan
                        </label>

                        <textarea
                            name="note"
                            id="note"
                            rows="3"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-[#3B8DBD] focus:ring-[#3B8DBD]"
                            placeholder="Contoh: Untuk kebutuhan operasional divisi bulan ini"
                        >{{ old('note') }}</textarea>

                        @error('note')
                            <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Header Daftar Barang --}}
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold leading-5 text-slate-800">
                                Daftar Barang yang Diminta
                            </h3>
                            <p class="text-[11px] leading-4 text-slate-500">
                                Minimal satu barang harus diisi.
                            </p>
                        </div>

                        <button
                            type="button"
                            onclick="addItemRow()"
                            class="inline-flex items-center gap-1.5 rounded-md bg-[#08B05A] px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-[#07934C]"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                            </svg>
                            Tambah Barang
                        </button>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto rounded-md border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="w-2/5 px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Barang
                                    </th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Stok Entitas
                                    </th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Jumlah Diminta
                                    </th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Keterangan
                                    </th>
                                    <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="items-wrapper" class="divide-y divide-slate-100 bg-white">
                                @foreach ($oldItems as $index => $oldItem)
                                    <tr class="item-row hover:bg-slate-50">
                                        <td class="px-4 py-2.5">
                                            <select
                                                name="items[{{ $index }}][item_id]"
                                                class="item-select block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-[#3B8DBD] focus:ring-[#3B8DBD]"
                                                onchange="updateStock(this)"
                                            >
                                                <option value="">-- Pilih Barang --</option>

                                                @foreach ($items as $item)
                                                    @php
                                                        $stocks = $item->itemStocks->mapWithKeys(function ($stock) {
                                                            return [
                                                                $stock->entity_id => [
                                                                    'stock' => $stock->current_stock,
                                                                    'minimum' => $stock->minimum_stock,
                                                                ]
                                                            ];
                                                        });
                                                    @endphp

                                                    <option
                                                        value="{{ $item->id }}"
                                                        data-unit="{{ $item->unit }}"
                                                        data-stocks='@json($stocks)'
                                                        @selected((string)($oldItem['item_id'] ?? '') === (string)$item->id)
                                                    >
                                                        {{ $item->code }} - {{ $item->name }} | {{ strtoupper($item->type) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-2.5">
                                            <span class="stock-text inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                                -
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-2.5">
                                            <input
                                                type="number"
                                                name="items[{{ $index }}][quantity]"
                                                min="1"
                                                value="{{ $oldItem['quantity'] ?? '' }}"
                                                class="w-24 rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-[#3B8DBD] focus:ring-[#3B8DBD]"
                                                placeholder="0"
                                            >
                                        </td>

                                        <td class="px-4 py-2.5">
                                            <input
                                                type="text"
                                                name="items[{{ $index }}][note]"
                                                value="{{ $oldItem['note'] ?? '' }}"
                                                class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-[#3B8DBD] focus:ring-[#3B8DBD]"
                                                placeholder="Opsional"
                                            >
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-2.5 text-center">
                                            <button
                                                type="button"
                                                onclick="removeItemRow(this)"
                                                class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-1.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-md border border-cyan-100 bg-cyan-50 px-3 py-2 text-[11px] text-cyan-800">
                        Stok yang ditampilkan mengikuti entitas yang dipilih. Jika entitas belum dipilih, stok belum tampil.
                    </div>
                </div>

                {{-- Footer Form --}}
                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <a href="{{ route('staff.requests.index') }}"
                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="rounded-md bg-[#08B05A] px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-[#07934C]"
                    >
                        Simpan Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let itemIndex = {{ count($oldItems) }};

        function selectedEntityId() {
            return document.getElementById('entity_id').value;
        }

        function updateStock(select) {
            const entityId = selectedEntityId();
            const selectedOption = select.options[select.selectedIndex];
            const row = select.closest('tr');
            const stockText = row.querySelector('.stock-text');

            if (!entityId) {
                stockText.textContent = 'Pilih entitas';
                stockText.className = 'stock-text inline-flex rounded-md bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-700';
                return;
            }

            if (!selectedOption || !selectedOption.value) {
                stockText.textContent = '-';
                stockText.className = 'stock-text inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700';
                return;
            }

            const unit = selectedOption.getAttribute('data-unit') || '';
            const stocks = JSON.parse(selectedOption.getAttribute('data-stocks') || '{}');
            const selectedStock = stocks[entityId];

            if (selectedStock) {
                const stock = selectedStock.stock ?? 0;
                const minimum = selectedStock.minimum ?? 0;

                stockText.textContent = stock + ' ' + unit + ' | Min: ' + minimum;

                if (stock <= minimum) {
                    stockText.className = 'stock-text inline-flex rounded-md bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700';
                } else {
                    stockText.className = 'stock-text inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700';
                }
            } else {
                stockText.textContent = 'Belum ada stok';
                stockText.className = 'stock-text inline-flex rounded-md bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700';
            }
        }

        function refreshAllStocks() {
            document.querySelectorAll('.item-select').forEach(function (select) {
                updateStock(select);
            });
        }

        function addItemRow() {
            const wrapper = document.getElementById('items-wrapper');
            const firstRow = wrapper.querySelector('.item-row');
            const newRow = firstRow.cloneNode(true);

            newRow.querySelectorAll('select, input').forEach(function (element) {
                const name = element.getAttribute('name');

                if (name) {
                    element.setAttribute('name', name.replace(/\[\d+\]/, '[' + itemIndex + ']'));
                }

                if (element.tagName === 'SELECT') {
                    element.selectedIndex = 0;
                } else {
                    element.value = '';
                }
            });

            newRow.querySelector('.stock-text').textContent = '-';
            newRow.querySelector('.stock-text').className = 'stock-text inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700';

            wrapper.appendChild(newRow);

            itemIndex++;
            refreshAllStocks();
        }

        function removeItemRow(button) {
            const rows = document.querySelectorAll('.item-row');

            if (rows.length <= 1) {
                alert('Minimal harus ada satu barang dalam pengajuan.');
                return;
            }

            button.closest('tr').remove();
        }

        document.addEventListener('DOMContentLoaded', function () {
            refreshAllStocks();
        });
    </script>
</x-app-layout>