<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-white">
                Tambah Stok Masuk
            </h1>

            <p class="text-[10px] leading-4 text-white/80">
                Catat penambahan stok berdasarkan entitas, gudang, dan barang.
            </p>
        </div>
    </x-slot>

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

                                @error('entity_id')
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
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

                                @error('warehouse_id')
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
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

                        <div>
                            <label for="item_id"
                                   class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                Barang
                                <span class="text-red-500">*</span>
                            </label>

                            <select name="item_id"
                                    id="item_id"
                                    required
                                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Barang --</option>

                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}"
                                            @selected(
                                                (string) old('item_id')
                                                === (string) $item->id
                                            )>
                                        {{ $item->code }} - {{ $item->name }}
                                        @if ($item->category)
                                            | {{ $item->category->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('item_id')
                                <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

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
                                <label for="quantity"
                                       class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                                    Jumlah Masuk
                                    <span class="text-red-500">*</span>
                                </label>

                                <input type="number"
                                       name="quantity"
                                       id="quantity"
                                       min="1"
                                       value="{{ old('quantity') }}"
                                       required
                                       class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm"
                                       placeholder="Contoh: 10">
                            </div>
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
                            Stok akan bertambah pada kombinasi entitas, gudang, dan barang yang dipilih.
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
</x-app-layout>
