@php
    $input = 'mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-xs focus:border-[#2C87B8] focus:ring-[#2C87B8]';
    $cell = 'w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs focus:border-[#2C87B8] focus:ring-[#2C87B8]';
    $bast = $form['bast'];
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <label class="text-[11px] font-semibold text-slate-600">Tanggal Serah Terima *
            <input type="date" name="bast[date]" value="{{ old('bast.date', $bast['date']) }}" required class="{{ $input }}">
        </label>
        <label class="text-[11px] font-semibold text-slate-600">Tempat
            <input type="text" name="bast[place]" value="{{ old('bast.place', $bast['place']) }}" maxlength="80" class="{{ $input }}">
        </label>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <fieldset class="rounded-md border border-slate-200 p-3">
            <legend class="px-1 text-[11px] font-bold text-slate-700">Pihak Pertama (Yang Menyerahkan)</legend>
            <div class="grid grid-cols-1 gap-2">
                <label class="text-[11px] font-semibold text-slate-600">Nama *
                    <input type="text" name="bast[first_name]" value="{{ old('bast.first_name', $bast['first_name']) }}" required maxlength="150" class="{{ $input }}">
                </label>
                <label class="text-[11px] font-semibold text-slate-600">NIK
                    <input type="text" name="bast[first_nik]" value="{{ old('bast.first_nik', $bast['first_nik']) }}" maxlength="30" class="{{ $input }}">
                </label>
                <label class="text-[11px] font-semibold text-slate-600">Jabatan
                    <input type="text" name="bast[first_position]" value="{{ old('bast.first_position', $bast['first_position']) }}" maxlength="150" class="{{ $input }}" placeholder="Contoh: Staff General Affairs">
                </label>
            </div>
        </fieldset>

        <fieldset class="rounded-md border border-slate-200 p-3">
            <legend class="px-1 text-[11px] font-bold text-slate-700">Pihak Kedua (Penerima / PIC)</legend>
            <div class="grid grid-cols-1 gap-2">
                <label class="text-[11px] font-semibold text-slate-600">Nama *
                    <input type="text" name="bast[second_name]" value="{{ old('bast.second_name', $bast['second_name']) }}" required maxlength="150" class="{{ $input }}">
                </label>
                <label class="text-[11px] font-semibold text-slate-600">NIK
                    <input type="text" name="bast[second_nik]" value="{{ old('bast.second_nik', $bast['second_nik']) }}" maxlength="30" class="{{ $input }}">
                </label>
                <label class="text-[11px] font-semibold text-slate-600">Jabatan
                    <input type="text" name="bast[second_position]" value="{{ old('bast.second_position', $bast['second_position']) }}" maxlength="150" class="{{ $input }}" placeholder="Contoh: Spv Cash Management">
                </label>
            </div>
        </fieldset>
    </div>

    <div class="overflow-x-auto rounded-md border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-xs">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-2 py-2 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                    <th class="px-2 py-2 text-left text-[10px] font-bold uppercase text-slate-500">Nama Barang *</th>
                    <th class="px-2 py-2 text-left text-[10px] font-bold uppercase text-slate-500">Spesifikasi</th>
                    <th class="px-2 py-2 text-left text-[10px] font-bold uppercase text-slate-500">Kode Asset</th>
                    <th class="px-2 py-2 text-left text-[10px] font-bold uppercase text-slate-500">Jumlah *</th>
                    <th class="px-2 py-2 text-left text-[10px] font-bold uppercase text-slate-500">Kondisi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($pengajuan->details as $detail)
                    @php $row = $form['items'][$detail->id]; @endphp
                    <tr class="align-top">
                        <td class="px-2 py-2 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-2 py-2">
                            <input type="text" name="items[{{ $detail->id }}][name]" value="{{ old("items.{$detail->id}.name", $row['name']) }}" required maxlength="255" class="{{ $cell }}">
                        </td>
                        <td class="px-2 py-2">
                            <textarea name="items[{{ $detail->id }}][specification]" rows="2" maxlength="1000" class="{{ $cell }}">{{ old("items.{$detail->id}.specification", $row['specification']) }}</textarea>
                        </td>
                        <td class="px-2 py-2">
                            <input type="text" name="items[{{ $detail->id }}][asset_code]" value="{{ old("items.{$detail->id}.asset_code", $row['asset_code']) }}" maxlength="100" class="{{ $cell }}" placeholder="Kode aset">
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" min="1" name="items[{{ $detail->id }}][quantity]" value="{{ old("items.{$detail->id}.quantity", $row['quantity']) }}" required class="{{ $cell }} w-20">
                        </td>
                        <td class="px-2 py-2">
                            <input type="text" name="items[{{ $detail->id }}][condition]" value="{{ old("items.{$detail->id}.condition", $row['condition']) }}" maxlength="100" class="{{ $cell }}" placeholder="Baik">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <fieldset class="rounded-md border border-slate-200 p-3">
        <legend class="px-1 text-[11px] font-bold text-slate-700">Mengetahui (opsional)</legend>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
            <label class="text-[11px] font-semibold text-slate-600">Nama
                <input type="text" name="bast[known_name]" value="{{ old('bast.known_name', $bast['known_name']) }}" maxlength="150" class="{{ $input }}">
            </label>
            <label class="text-[11px] font-semibold text-slate-600">Jabatan
                <input type="text" name="bast[known_position]" value="{{ old('bast.known_position', $bast['known_position']) }}" maxlength="150" class="{{ $input }}" placeholder="Contoh: Spv. GA">
            </label>
        </div>
    </fieldset>
</div>
