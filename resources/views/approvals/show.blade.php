<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold leading-5 text-white">
                    Detail Approval Pengajuan
                </h1>
                <p class="text-[10px] leading-4 text-white/80">
                    Periksa stok entitas dan tentukan jumlah barang yang disetujui.
                </p>
            </div>

            <a href="{{ route('approvals.index') }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-white/20">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali
            </a>
        </div>
    </x-slot>

    @php
        $statusClass = match ($approval->status) {
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'rejected' => 'bg-red-50 text-red-700 ring-red-200',
            default => 'bg-amber-50 text-amber-700 ring-amber-200',
        };

        $statusText = match ($approval->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Pending',
        };
    @endphp

    <div class="space-y-3">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

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
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">
                            Informasi Pengajuan
                        </h2>
                        <p class="text-[10px] text-slate-500">
                            Nomor: {{ $approval->request_number }}
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
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">
                            {{ $approval->request_number }}
                        </p>
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-medium text-slate-500">Entitas</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">
                            {{ $approval->entity->code ?? '-' }} - {{ $approval->entity->name ?? '-' }}
                        </p>
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-medium text-slate-500">Pemohon</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">
                            {{ $approval->user->name ?? '-' }}
                        </p>
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-medium text-slate-500">Tanggal Pengajuan</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">
                            {{ $approval->request_date ? $approval->request_date->format('d/m/Y') : '-' }}
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
                            {{ $approval->approver->name ?? '-' }}
                        </p>
                    </div>
                </div>

                <div class="mt-3">
                    <p class="mb-1 text-[10px] font-semibold text-slate-600">Catatan Staff</p>
                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] text-slate-700">
                        {{ $approval->note ?: '-' }}
                    </div>
                </div>

                @if ($approval->status !== 'pending')
                    <div class="mt-3">
                        <p class="mb-1 text-[10px] font-semibold text-slate-600">Catatan Approver</p>
                        <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] text-slate-700">
                            {{ $approval->approver_note ?: '-' }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold text-slate-800">
                    Detail Barang Pengajuan
                </h2>
                <p class="text-[10px] text-slate-500">
                    Stok tersedia yang tampil adalah stok milik entitas {{ $approval->entity->code ?? '-' }}.
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
                                    <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Stok Entitas</th>
                                    <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Diminta</th>
                                    <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Disetujui</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Catatan</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($approval->details as $detail)
                                    @php
                                        $entityStock = $detail->item?->itemStocks?->firstWhere('entity_id', $approval->entity_id);
                                        $availableStock = $entityStock ? $entityStock->current_stock : 0;
                                        $maxApprove = min($detail->quantity, $availableStock);
                                    @endphp

                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td class="px-4 py-2.5">
                                            <p class="text-xs font-semibold text-slate-800">
                                                {{ $detail->item->name ?? '-' }}
                                            </p>
                                            <p class="text-[10px] text-slate-500">
                                                {{ $detail->item->code ?? '-' }} | {{ $detail->item->category->name ?? '-' }}
                                            </p>
                                        </td>

                                        <td class="px-4 py-2.5 text-right">
                                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                                {{ $availableStock }} {{ $detail->item->unit ?? '' }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                            {{ $detail->quantity }} {{ $detail->item->unit ?? '' }}
                                        </td>

                                        <td class="px-4 py-2.5 text-right">
                                            <input
                                                type="number"
                                                name="approved_quantity[{{ $detail->id }}]"
                                                min="0"
                                                max="{{ $detail->quantity }}"
                                                value="{{ old('approved_quantity.' . $detail->id, $maxApprove) }}"
                                                class="w-24 rounded-md border-slate-300 px-3 py-1.5 text-right text-xs shadow-sm focus:border-[#3B8DBD] focus:ring-[#3B8DBD]"
                                            >
                                            <p class="mt-1 text-[10px] text-slate-400">
                                                Maks: {{ $detail->quantity }}
                                            </p>
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
                        <label for="approver_note" class="mb-1.5 block text-[11px] font-semibold text-slate-700">
                            Catatan Approver
                        </label>

                        <textarea
                            name="approver_note"
                            id="approver_note"
                            rows="3"
                            class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-[#3B8DBD] focus:ring-[#3B8DBD]"
                            placeholder="Catatan persetujuan jika ada"
                        >{{ old('approver_note') }}</textarea>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-800"
                            onclick="return confirm('Yakin ingin menyetujui pengajuan ini? Stok entitas akan otomatis berkurang.')"
                        >
                            Setujui Pengajuan
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('approvals.reject', $approval) }}" class="border-t border-slate-200 bg-slate-50 px-4 pb-3 sm:flex sm:justify-end">
                    @csrf

                    <input type="hidden" name="approver_note" value="Pengajuan ditolak">

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-md bg-red-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-red-700 sm:w-auto"
                        onclick="return confirm('Yakin ingin menolak pengajuan ini?')"
                    >
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
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Catatan</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($approval->details as $detail)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-4 py-2.5">
                                        <p class="text-xs font-semibold text-slate-800">
                                            {{ $detail->item->name ?? '-' }}
                                        </p>
                                        <p class="text-[10px] text-slate-500">
                                            {{ $detail->item->code ?? '-' }} | {{ $detail->item->category->name ?? '-' }}
                                        </p>
                                    </td>

                                    <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                        {{ $detail->quantity }} {{ $detail->item->unit ?? '' }}
                                    </td>

                                    <td class="px-4 py-2.5 text-right">
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            {{ $detail->approved_quantity }} {{ $detail->item->unit ?? '' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                        {{ $detail->note ?: '-' }}
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