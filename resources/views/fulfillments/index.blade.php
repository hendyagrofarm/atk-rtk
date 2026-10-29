<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-white">Pembelanjaan &amp; BAST</h1>
            <p class="text-[10px] leading-4 text-white/80">
                Pantau pembelian barang pengajuan custom sampai diterima pengguna.
            </p>
        </div>
    </x-slot>

    @php
        $stageClass = fn ($stage) => match ($stage) {
            'received', 'handed_over' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            null => 'bg-slate-50 text-slate-600 ring-slate-200',
            default => 'bg-amber-50 text-amber-700 ring-amber-200',
        };
    @endphp

    <div class="space-y-3">
        <div class="flex gap-2 text-[11px] font-semibold">
            <a href="{{ route('fulfillments.index') }}"
               class="rounded-md px-3 py-1.5 {{ $tab === 'proses' ? 'bg-[#2C87B8] text-white' : 'border border-slate-200 bg-white text-slate-600' }}">
                Dalam Proses ({{ $counts['proses'] }})
            </a>
            <a href="{{ route('fulfillments.index', ['tab' => 'selesai']) }}"
               class="rounded-md px-3 py-1.5 {{ $tab === 'selesai' ? 'bg-[#2C87B8] text-white' : 'border border-slate-200 bg-white text-slate-600' }}">
                Selesai ({{ $counts['selesai'] }})
            </a>
        </div>

        <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Pengajuan</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">PIC / Departemen</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Barang</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Tahap</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $pengajuan)
                            @php
                                $stage = \App\Support\Fulfillment::currentStage($pengajuan);
                                $first = $pengajuan->details->first();
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="text-xs font-bold text-slate-800">{{ $pengajuan->request_number }}</p>
                                    <p class="text-[10px] text-slate-500">Disetujui {{ $pengajuan->approved_at?->format('d-m-Y') ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-[12px] text-slate-600">
                                    {{ $pengajuan->pic_name ?: $pengajuan->user?->name }}
                                    <p class="text-[10px] text-slate-400">{{ $pengajuan->department ?: '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-[12px] text-slate-600">
                                    {{ $first?->custom_item_name ?? '-' }}
                                    @if ($pengajuan->details->count() > 1)
                                        <span class="text-[10px] text-slate-400">+{{ $pengajuan->details->count() - 1 }} lain</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset {{ $stageClass($stage) }}">
                                        {{ $stage ? \App\Models\RequestFulfillment::STAGES[$stage] : 'Belum diproses' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('fulfillments.show', $pengajuan) }}"
                                       class="rounded-md bg-[#2C87B8] px-3 py-1.5 text-[10px] font-bold text-white hover:bg-[#256f96]">
                                        {{ $tab === 'selesai' ? 'Lihat' : 'Proses' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-xs text-slate-500">
                                    Tidak ada pengajuan {{ $tab === 'selesai' ? 'yang sudah selesai' : 'dalam proses' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $requests->links() }}
    </div>
</x-app-layout>
