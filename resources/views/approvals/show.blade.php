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

                @if ($approval->status === 'rejected')
                    <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 md:col-span-4">
                        <p class="text-[10px] text-red-600">Alasan Penolakan</p>
                        <p class="text-xs font-semibold text-red-800">
                            {{ $approval->rejection_reason_label ?? '-' }}
                        </p>

                        @if ($approval->approver_note)
                            <p class="mt-0.5 text-[11px] text-red-700">{{ $approval->approver_note }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if ($approval->usesApprovalChain())
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">Alur Approval</h2>
                        <p class="text-[10px] text-slate-500">
                            @php
                                $picName = filled($approval->pic_name) ? $approval->pic_name : ($approval->user?->name ?? '-');
                                $picPosition = filled($approval->pic_name)
                                    ? $approval->pic_position
                                    : $approval->user?->position?->name;
                            @endphp

                            Diajukan oleh {{ $picName }}{{ $picPosition ? ' (' . $picPosition . ')' : '' }}
                            @if ($approval->department) | {{ $approval->department }} @endif
                            @if (filled($approval->pic_name) && $approval->user && $approval->user->name !== $approval->pic_name)
                                | dibuat oleh {{ $approval->user->name }}
                            @endif
                            @if ($approval->superior_name)
                                | atasan langsung: {{ $approval->superior_name }}
                            @endif
                        </p>
                    </div>

                    <a href="{{ route('fpb.print', $approval) }}" target="_blank"
                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                        Cetak FPB
                    </a>
                </div>

                @if ($approval->status === 'pending' && $currentStep)
                    <div class="border-b border-amber-100 bg-amber-50 px-4 py-2 text-[11px] text-amber-800">
                        Menunggu <span class="font-semibold">{{ $currentStep->label }} {{ $currentStep->position_name }}</span>
                        @if ($canAct)
                            - giliran Anda.
                        @else
                            - Anda tidak berwenang memproses tahap ini.
                        @endif
                    </div>
                @endif

                <ol class="divide-y divide-slate-100">
                    @foreach ($approval->approvals as $step)
                        @php
                            $badge = match ($step->status) {
                                'approved' => ['Disetujui', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
                                'rejected' => ['Ditolak', 'bg-red-50 text-red-700 ring-red-200'],
                                'skipped' => ['Dilewati', 'bg-slate-50 text-slate-500 ring-slate-200'],
                                default => ['Menunggu', 'bg-amber-50 text-amber-700 ring-amber-200'],
                            };
                        @endphp

                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                            <div>
                                <p class="text-[10px] text-slate-500">{{ $loop->iteration }}. {{ $step->label }}</p>
                                <p class="text-xs font-semibold text-slate-800">{{ $step->position_name }}</p>
                                @if ($step->user)
                                    <p class="text-[10px] text-slate-600">
                                        {{ $step->user->name }} | {{ $step->acted_at?->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                                @if ($step->note)
                                    <p class="text-[10px] italic text-slate-500">"{{ $step->note }}"</p>
                                @endif
                            </div>

                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $badge[1] }}">{{ $badge[0] }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
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

            @if ($approval->status === 'pending' && $canAct)
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

                                                @if ($detail->quantity > $availableStock)
                                                    <p class="mt-0.5 text-[10px] font-bold text-red-600">
                                                        {{ $availableStock <= 0 ? 'Stok kosong' : 'Kurang ' . ($detail->quantity - $availableStock) }}
                                                    </p>
                                                @endif
                                            @endif
                                        </td>

                                        <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                            {{ $detail->quantity }}
                                            {{ $detail->item?->unit ?? $detail->unit ?? '' }}
                                        </td>

                                        <td class="px-4 py-2.5 text-right">
                                            @if ($approval->usesApprovalChain() && ! $isFinalStep)
                                                <span class="text-[10px] text-slate-400">Ditetapkan di tahap akhir</span>
                                            @else
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
                                            @endif
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
                        @php
                            $approveLabel = $approval->usesApprovalChain() && ! $isFinalStep
                                ? 'Setujui Tahap Ini'
                                : 'Setujui Pengajuan';
                            $approveConfirm = $approval->request_type === 'custom'
                                ? ($approval->usesApprovalChain() && ! $isFinalStep
                                    ? 'Setujui tahap ini dan teruskan ke tahap berikutnya?'
                                    : 'Setujui pengajuan custom ini? Stok tidak berubah.')
                                : 'Setujui pengajuan dan kurangi stok lokasi ini?';
                        @endphp

                        <button type="submit"
                                onclick="return confirm('{{ $approveConfirm }}')"
                                class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">
                            {{ $approveLabel }}
                        </button>
                    </div>
                </form>

                <form method="POST"
                      action="{{ route('approvals.reject', $approval) }}"
                      class="space-y-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    @csrf

                    <p class="text-[11px] font-semibold text-slate-700">
                        Atau tolak pengajuan ini
                    </p>

                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                        <div>
                            <label for="rejection_reason"
                                   class="mb-1 block text-[10px] font-semibold text-slate-600">
                                Alasan Penolakan <span class="text-red-500">*</span>
                            </label>

                            <select name="rejection_reason"
                                    id="rejection_reason"
                                    required
                                    class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                                <option value="">-- Pilih Alasan --</option>
                                @foreach (\App\Models\Request::REJECTION_REASONS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('rejection_reason') === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="reject_note"
                                   class="mb-1 block text-[10px] font-semibold text-slate-600">
                                Catatan Penolakan
                            </label>

                            <input type="text"
                                   name="approver_note"
                                   id="reject_note"
                                   maxlength="3000"
                                   placeholder="Opsional"
                                   class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                onclick="return confirm('Tolak pengajuan ini?')"
                                class="rounded-md bg-red-600 px-3 py-1.5 text-[11px] font-semibold text-white">
                            Tolak Pengajuan
                        </button>
                    </div>
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
