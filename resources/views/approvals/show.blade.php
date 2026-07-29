<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold text-white">
                    Detail Approval Pengajuan
                </h1>

                <p class="text-[10px] text-white/80">
                    Periksa stok berdasarkan entitas dan gudang sumber.
                </p>
            </div>

            <a href="{{ route('approvals.index') }}"
               class="rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white">
                Kembali
            </a>
        </div>
    </x-slot>

    @php
        $statusClass = match ($approval->status) {
            'approved' => 'bg-emerald-50 text-emerald-700',
            'rejected' => 'bg-red-50 text-red-700',
            default => 'bg-amber-50 text-amber-700',
        };

        $statusText = match ($approval->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Pending',
        };
    @endphp

    <div class="space-y-3">
        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">
                            Informasi Pengajuan
                        </h2>

                        <p class="text-[10px] text-slate-500">
                            {{ $approval->request_number }}
                        </p>
                    </div>

                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                        {{ $statusText }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 px-4 py-4 md:grid-cols-3">
                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Entitas</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $approval->entity?->code ?? '-' }} -
                        {{ $approval->entity?->name ?? '-' }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Gudang Sumber</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $approval->warehouse?->name ?? '-' }}
                    </p>
                    <p class="text-[9px] text-slate-500">
                        {{ $approval->warehouse?->code ?? '-' }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Pemohon</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $approval->user?->name ?? '-' }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Tanggal</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $approval->request_date?->format('d/m/Y') ?? '-' }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 md:col-span-2">
                    <p class="text-[10px] text-slate-500">Catatan Staff</p>
                    <p class="text-[11px] text-slate-700">
                        {{ $approval->note ?: '-' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold text-slate-800">
                    Detail Barang
                </h2>

                <p class="text-[10px] text-slate-500">
                    Stok tersedia berasal dari
                    {{ $approval->entity?->code ?? '-' }}
                    /
                    {{ $approval->warehouse?->name ?? '-' }}.
                </p>
            </div>

            @if ($approval->status === 'pending')
                <form method="POST" action="{{ route('approvals.approve', $approval) }}">
                    @csrf

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Barang</th>
                                    <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Stok Lokasi</th>
                                    <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Diminta</th>
                                    <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Disetujui</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Catatan</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach ($approval->details as $detail)
                                    @php
                                        $locationStock = $detail->item?->itemStocks
                                            ?->first(function ($stock) use ($approval) {
                                                return
                                                    (int) $stock->entity_id
                                                        === (int) $approval->entity_id
                                                    && (int) $stock->warehouse_id
                                                        === (int) $approval->warehouse_id;
                                            });

                                        $availableStock = $locationStock?->current_stock ?? 0;
                                        $maxApprove = min(
                                            $detail->quantity,
                                            $availableStock
                                        );
                                    @endphp

                                    <tr>
                                        <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td class="px-4 py-2.5">
                                            <p class="text-xs font-semibold text-slate-800">
                                                {{ $detail->item?->name ?? $detail->custom_item_name ?? '-' }}
                                            </p>

                                            <p class="text-[10px] text-slate-500">
                                                {{ $detail->item?->code ?? 'Custom' }}
                                            </p>
                                        </td>

                                        <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                            @if ($approval->request_type === 'custom')
                                                Tidak menggunakan stok
                                            @else
                                                {{ $availableStock }}
                                                {{ $detail->item?->unit ?? '' }}
                                            @endif
                                        </td>

                                        <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                            {{ $detail->quantity }}
                                            {{ $detail->item?->unit ?? $detail->unit ?? '' }}
                                        </td>

                                        <td class="px-4 py-2.5 text-right">
                                            <input type="number"
                                                   name="approved_quantity[{{ $detail->id }}]"
                                                   min="0"
                                                   max="{{ $approval->request_type === 'custom' ? $detail->quantity : $maxApprove }}"
                                                   value="{{ old(
                                                       'approved_quantity.' . $detail->id,
                                                       $approval->request_type === 'custom'
                                                           ? $detail->quantity
                                                           : $maxApprove
                                                   ) }}"
                                                   required
                                                   class="w-24 rounded-md border-slate-300 px-3 py-1.5 text-right text-xs">
                                        </td>

                                        <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                            {{ $detail->note ?: '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-200 px-4 py-4">
                        <label class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                            Catatan Approver
                        </label>

                        <textarea name="approver_note"
                                  rows="3"
                                  maxlength="3000"
                                  class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">{{ old('approver_note') }}</textarea>
                    </div>

                    <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-4 py-3">
                        <button type="submit"
                                onclick="return confirm('Setujui pengajuan dan kurangi stok lokasi ini?')"
                                class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">
                            Setujui Pengajuan
                        </button>
                    </div>
                </form>

                <form method="POST"
                      action="{{ route('approvals.reject', $approval) }}"
                      class="flex justify-end border-t border-slate-200 bg-slate-50 px-4 pb-3">
                    @csrf

                    <input type="hidden"
                           name="approver_note"
                           value="Pengajuan ditolak">

                    <button type="submit"
                            onclick="return confirm('Tolak pengajuan ini?')"
                            class="rounded-md bg-red-600 px-3 py-1.5 text-[11px] font-semibold text-white">
                        Tolak Pengajuan
                    </button>
                </form>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Barang</th>
                                <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Diminta</th>
                                <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Disetujui</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($approval->details as $detail)
                                <tr>
                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-4 py-2.5 text-xs font-semibold text-slate-800">
                                        {{ $detail->item?->name ?? $detail->custom_item_name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-2.5 text-right text-xs text-slate-700">
                                        {{ $detail->quantity }}
                                    </td>

                                    <td class="px-4 py-2.5 text-right text-xs font-semibold text-emerald-700">
                                        {{ $detail->approved_quantity }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
