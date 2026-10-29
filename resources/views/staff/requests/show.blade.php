<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold leading-5 text-white">
                    Detail Pengajuan ATK/RTK
                </h1>

                <p class="text-[10px] leading-4 text-white/80">
                    Detail pengajuan, lokasi stok, dan status persetujuan.
                </p>
            </div>

            <a href="{{ route('staff.requests.index') }}"
               class="rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white">
                Kembali
            </a>
        </div>
    </x-slot>

    @php
        $statusClass = match ($request->status) {
            'approved' => 'bg-emerald-50 text-emerald-700',
            'rejected' => 'bg-red-50 text-red-700',
            default => 'bg-amber-50 text-amber-700',
        };

        $statusText = match ($request->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Pending',
        };
    @endphp

    <div class="space-y-3">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">
                            Informasi Pengajuan
                        </h2>

                        <p class="text-[10px] text-slate-500">
                            {{ $request->request_number }}
                        </p>
                    </div>

                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                        {{ $statusText }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 px-4 py-4 md:grid-cols-3">
                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Nomor Pengajuan</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $request->request_number }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Entitas</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $request->entity?->code ?? '-' }} -
                        {{ $request->entity?->name ?? '-' }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Gudang Sumber</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $request->warehouse?->name ?? '-' }}
                    </p>
                    <p class="text-[9px] text-slate-500">
                        {{ $request->warehouse?->code ?? '-' }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Tanggal Pengajuan</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $request->request_date?->format('d/m/Y') ?? '-' }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Approver</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $request->approver?->name ?? '-' }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[10px] text-slate-500">Tanggal Diproses</p>
                    <p class="text-xs font-semibold text-slate-800">
                        {{ $request->approved_at?->format('d/m/Y H:i') ?? '-' }}
                    </p>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 md:col-span-3">
                    <p class="text-[10px] text-slate-500">Catatan Staff</p>
                    <p class="text-[11px] text-slate-700">
                        {{ $request->note ?: '-' }}
                    </p>
                </div>

                @if ($request->status === 'rejected' && $request->rejection_reason_label)
                    <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 md:col-span-3">
                        <p class="text-[10px] text-red-600">Alasan Penolakan</p>
                        <p class="text-xs font-semibold text-red-800">
                            {{ $request->rejection_reason_label }}
                        </p>
                    </div>
                @endif

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 md:col-span-3">
                    <p class="text-[10px] text-slate-500">Catatan Approver</p>
                    <p class="text-[11px] text-slate-700">
                        {{ $request->approver_note ?: '-' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                <div>
                    <h2 class="text-xs font-semibold text-slate-800">Lacak Pengajuan</h2>
                    <p class="text-[10px] text-slate-500">
                        Riwayat proses pengajuan, dari yang terbaru. Waktu dalam {{ now()->format('T') }}.
                    </p>
                </div>

                @if ($request->isCustomRequest())
                    @if ($request->status === 'approved')
                        <a href="{{ route('fpb.print', $request->id) }}" target="_blank"
                           class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                            Cetak FPB
                        </a>

                        @if ($fulfillmentComplete)
                            <a href="{{ route('bast.print', $request->id) }}" target="_blank"
                               class="rounded-md bg-[#08B05A] px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-600">
                                Cetak BAST
                            </a>
                        @endif
                    @else
                        <span class="text-[10px] text-slate-400">FPB dapat dicetak setelah semua tahap disetujui</span>
                    @endif
                @endif
            </div>

            @include('partials.request-tracking', ['events' => $events])
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold text-slate-800">
                    Daftar Barang
                </h2>

                <p class="text-[10px] text-slate-500">
                    Stok saat ini berasal dari
                    {{ $request->entity?->code ?? '-' }}
                    /
                    {{ $request->warehouse?->name ?? '-' }}.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Kode</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Nama Barang</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Stok Lokasi</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Diminta</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Disetujui</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Keterangan</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($request->details as $detail)
                            @php
                                $locationStock = $detail->item?->itemStocks
                                    ?->first(function ($stock) use ($request) {
                                        return
                                            (int) $stock->entity_id
                                                === (int) $request->entity_id
                                            && (int) $stock->warehouse_id
                                                === (int) $request->warehouse_id;
                                    });
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $detail->item?->code ?? 'CUSTOM' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-xs font-semibold text-slate-800">
                                        {{ $detail->item?->name ?? $detail->custom_item_name ?? '-' }}
                                    </p>

                                    <p class="text-[10px] text-slate-500">
                                        {{ $detail->item?->category?->name ?? $detail->custom_category ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                    @if ($request->request_type === 'custom')
                                        -
                                    @else
                                        {{ $locationStock?->current_stock ?? 0 }}
                                        {{ $detail->item?->unit ?? '' }}
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                    {{ $detail->quantity }}
                                    {{ $detail->item?->unit ?? $detail->unit ?? '' }}
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                        {{ $detail->approved_quantity ?? 0 }}
                                        {{ $detail->item?->unit ?? $detail->unit ?? '' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $detail->note ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Tidak ada detail barang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
