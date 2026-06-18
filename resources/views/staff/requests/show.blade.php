<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold leading-5 text-slate-800">
                    Detail Pengajuan ATK/RTK
                </h1>
                <p class="text-[10px] leading-4 text-slate-500">
                    Detail pengajuan dan status persetujuan.
                </p>
            </div>

            <a href="{{ route('staff.requests.index') }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                Kembali
            </a>
        </div>
    </x-slot>

    @php
        $statusClass = match ($request->status) {
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'rejected' => 'bg-red-50 text-red-700 ring-red-200',
            default => 'bg-amber-50 text-amber-700 ring-amber-200',
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
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xs font-semibold leading-5 text-slate-800">
                            Informasi Pengajuan
                        </h2>
                        <p class="text-[10px] leading-4 text-slate-500">
                            Nomor pengajuan: {{ $request->request_number }}
                        </p>
                    </div>

                    <span class="inline-flex w-fit rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $statusClass }}">
                        {{ $statusText }}
                    </span>
                </div>
            </div>

            <div class="px-4 py-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-medium text-slate-500">Nomor Pengajuan</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">{{ $request->request_number }}</p>
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-medium text-slate-500">Entitas</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">
                            {{ $request->entity->code ?? '-' }} - {{ $request->entity->name ?? '-' }}
                        </p>
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-medium text-slate-500">Tanggal Pengajuan</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">
                            {{ $request->request_date ? $request->request_date->format('d/m/Y') : '-' }}
                        </p>
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-medium text-slate-500">Status</p>
                        <span class="mt-0.5 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $statusClass }}">
                            {{ $statusText }}
                        </span>
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-medium text-slate-500">Approver</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">
                            {{ $request->approver->name ?? '-' }}
                        </p>
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-medium text-slate-500">Tanggal Diproses</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">
                            {{ $request->approved_at ? $request->approved_at->format('d/m/Y H:i') : '-' }}
                        </p>
                    </div>
                </div>

                <div class="mt-3">
                    <p class="mb-1 text-[10px] font-semibold text-slate-600">Catatan Staff</p>
                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] text-slate-700">
                        {{ $request->note ?: '-' }}
                    </div>
                </div>

                <div class="mt-3">
                    <p class="mb-1 text-[10px] font-semibold text-slate-600">Catatan Approver</p>
                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] text-slate-700">
                        {{ $request->approver_note ?: '-' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold leading-5 text-slate-800">
                    Daftar Barang yang Diminta
                </h2>
                <p class="text-[10px] leading-4 text-slate-500">
                    Detail barang, stok entitas, jumlah diminta, dan jumlah disetujui.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Kode</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Nama Barang</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Stok Entitas</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Diminta</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Disetujui</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Keterangan</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($request->details as $detail)
                            @php
                                $entityStock = $detail->item?->itemStocks?->firstWhere('entity_id', $request->entity_id);
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">{{ $loop->iteration }}</td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $detail->item->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-xs font-semibold text-slate-800">{{ $detail->item->name ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $detail->item->category->name ?? '-' }}</p>
                                </td>

                                <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                    {{ $entityStock ? $entityStock->current_stock : '-' }}
                                    {{ $detail->item->unit ?? '' }}
                                </td>

                                <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                    {{ $detail->quantity }} {{ $detail->item->unit ?? '' }}
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    @if ($detail->approved_quantity === null)
                                        <span class="text-[11px] text-slate-400">-</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            {{ $detail->approved_quantity }} {{ $detail->item->unit ?? '' }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $detail->note ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-[11px] text-slate-500">
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