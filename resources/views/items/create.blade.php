<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Tambah Barang
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Tambahkan satu atau beberapa barang ATK/RTK baru sekaligus.
            </p>
        </div>
    </x-slot>

    @php
        $oldRows = old('items', [[
            'code' => '',
            'name' => '',
            'category_id' => '',
            'type' => '',
            'unit' => '',
            'minimum_stock' => 0,
            'storage_location' => '',
        ]]);

        $categoryOptions = $categories->map(fn ($category) => [
            'id' => (string) $category->id,
            'name' => $category->name,
        ])->values();

        $rowErrors = collect($errors->getMessages())
            ->filter(fn ($messages, $key) => str_starts_with($key, 'items.'))
            ->map(fn ($messages) => $messages[0]);
    @endphp

    <div class="mx-auto max-w-4xl">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-indigo-100 text-indigo-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-xs font-semibold leading-5 text-slate-800">
                                Form Tambah Barang
                            </h2>
                            <p class="text-[10px] leading-4 text-slate-500">
                                Isi data tiap barang. Stok awal dibuat lewat transaksi Stok Masuk.
                            </p>
                        </div>
                    </div>

                    <button type="button" id="add_row"
                            class="rounded-md bg-emerald-600 px-2.5 py-1.5 text-[10px] font-semibold text-white hover:bg-emerald-700">
                        + Tambah Barang
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('items.store') }}">
                @csrf

                <div class="space-y-3 px-4 py-4">
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

                    <div id="rows" class="space-y-3"></div>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <span id="row_count" class="text-[10px] text-slate-500"></span>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('items.index') }}"
                           class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                            Batal
                        </a>

                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800">
                            Simpan Semua
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categories = @json($categoryOptions);
            const initialRows = Object.entries(@json($oldRows));
            const rowErrors = @json($rowErrors);
            const rowsWrapper = document.getElementById('rows');
            const rowCount = document.getElementById('row_count');
            const fieldClass = 'block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
            let rowIndex = 0;

            function esc(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[char];
                });
            }

            function field(label, name, control, error) {
                return '<div>' +
                    '<label class="mb-1 block text-[10px] font-semibold text-slate-700">' + label + '</label>' +
                    control +
                    (error ? '<p class="mt-1 text-[10px] text-red-600">' + esc(error) + '</p>' : '') +
                '</div>';
            }

            function input(index, name, type, value, extra, hasError) {
                return '<input type="' + type + '" name="items[' + index + '][' + name + ']" value="' + esc(value) + '" ' +
                    (extra || '') + ' class="' + fieldClass + (hasError ? ' border-red-300' : '') + '">';
            }

            function renumber() {
                const rows = rowsWrapper.querySelectorAll('.item-row');

                rows.forEach(function (row, position) {
                    row.querySelector('.row-number').textContent = 'Barang ' + (position + 1);
                });

                rowCount.textContent = rows.length + ' barang akan disimpan';
            }

            function addRow(data, key) {
                data = data || {};
                const index = key !== undefined ? Number(key) : rowIndex;
                rowIndex = Math.max(rowIndex, index + 1);
                const err = function (name) { return rowErrors['items.' + index + '.' + name]; };

                const categoryOptions = '<option value="">-- Pilih Kategori --</option>' + categories.map(function (category) {
                    return '<option value="' + esc(category.id) + '"' +
                        (String(data.category_id ?? '') === category.id ? ' selected' : '') + '>' + esc(category.name) + '</option>';
                }).join('');

                const typeOptions = '<option value="">-- Pilih Jenis --</option>' +
                    ['ATK', 'RTK'].map(function (type) {
                        return '<option value="' + type + '"' + (data.type === type ? ' selected' : '') + '>' + type + '</option>';
                    }).join('');

                const row = document.createElement('div');
                row.className = 'item-row rounded-md border border-slate-200 bg-slate-50 p-3';
                row.innerHTML =
                    '<div class="mb-2 flex items-center justify-between">' +
                        '<span class="row-number text-[11px] font-bold text-slate-700"></span>' +
                        '<button type="button" class="remove-row rounded-md bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700">Hapus</button>' +
                    '</div>' +
                    '<div class="grid grid-cols-1 gap-2 md:grid-cols-3">' +
                        field('Kode Barang *', 'code', input(index, 'code', 'text', data.code, 'required autocomplete="off" placeholder="Contoh: ATK001"', err('code')), err('code')) +
                        field('Nama Barang *', 'name', input(index, 'name', 'text', data.name, 'required autocomplete="off" placeholder="Contoh: Pulpen Biru"', err('name')), err('name')) +
                        field('Kategori *', 'category_id', '<select name="items[' + index + '][category_id]" required class="' + fieldClass + '">' + categoryOptions + '</select>', err('category_id')) +
                        field('Jenis *', 'type', '<select name="items[' + index + '][type]" required class="' + fieldClass + '">' + typeOptions + '</select>', err('type')) +
                        field('Satuan *', 'unit', input(index, 'unit', 'text', data.unit, 'required autocomplete="off" placeholder="pcs, box, pack"', err('unit')), err('unit')) +
                        field('Stok Minimum *', 'minimum_stock', input(index, 'minimum_stock', 'number', data.minimum_stock ?? 0, 'min="0" required', err('minimum_stock')), err('minimum_stock')) +
                    '</div>' +
                    '<div class="mt-2">' +
                        field('Lokasi Penyimpanan', 'storage_location', input(index, 'storage_location', 'text', data.storage_location, 'autocomplete="off" placeholder="Contoh: Lemari ATK"', err('storage_location')), err('storage_location')) +
                    '</div>';

                rowsWrapper.appendChild(row);

                row.querySelector('.remove-row').addEventListener('click', function () {
                    if (rowsWrapper.querySelectorAll('.item-row').length <= 1) {
                        alert('Minimal harus ada satu barang.');
                        return;
                    }
                    row.remove();
                    renumber();
                });

                renumber();

                return row;
            }

            document.getElementById('add_row').addEventListener('click', function () {
                const previous = rowsWrapper.lastElementChild;
                const row = addRow();

                // Salin kategori, jenis, dan satuan dari baris sebelumnya agar input massal lebih cepat.
                if (previous) {
                    ['category_id', 'type', 'unit'].forEach(function (name) {
                        const source = previous.querySelector('[name$="[' + name + ']"]');
                        const target = row.querySelector('[name$="[' + name + ']"]');
                        if (source && target) { target.value = source.value; }
                    });
                }

                row.querySelector('[name$="[code]"]').focus();
            });

            initialRows.forEach(function (entry) { addRow(entry[1], entry[0]); });
        });
    </script>
</x-app-layout>
