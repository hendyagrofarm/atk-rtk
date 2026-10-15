<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold leading-5 text-white">
                    Pengajuan Saya
                </h1>

                <p class="text-[10px] leading-4 text-white/80">
                    Riwayat pengajuan berdasarkan entitas dan gudang sumber.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if (auth()->user()->role === 'staff')
                    <a href="{{ route('staff.requests.create') }}"
                       class="inline-flex items-center rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                        Buat Pengajuan
                    </a>
                @endif

                <a href="{{ route('staff.requests.custom.create') }}"
                   class="inline-flex items-center rounded-md bg-sky-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-sky-800">
                    Pengajuan Custom (FPB)
                </a>
            </div>
        </div>
    </x-slot>

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

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold text-slate-800">
                    Data Pengajuan
                </h2>

                <p class="text-[10px] text-slate-500">
                    Daftar pengajuan yang Anda buat.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Nomor</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Gudang</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Tanggal</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Item</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Status</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($requests as $request)
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

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $requests->firstItem() + $loop->index }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $request->request_number }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                        {{ $request->entity?->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-[11px] font-semibold text-slate-700">
                                        {{ $request->warehouse?->name ?? '-' }}
                                    </p>

                                    <p class="text-[9px] text-slate-500">
                                        {{ $request->warehouse?->code ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $request->request_date?->format('d/m/Y') ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-center text-[11px] font-semibold text-slate-700">
                                    {{ $request->details_count }} item
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>

                                    @php
                                        $progress = \App\Support\RequestTimeline::summary($request);
                                    @endphp

                                    <p class="mt-1 text-[10px] font-medium leading-3 {{ $progress['state'] === 'rejected' ? 'text-red-600' : ($progress['state'] === 'done' ? 'text-emerald-700' : 'text-amber-700') }}">
                                        {{ $progress['label'] }}
                                    </p>

                                    @if ($progress['time'])
                                        <p class="text-[9px] leading-3 text-slate-400">{{ $progress['time'] }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 text-center">
                                    <a href="{{ route('staff.requests.show', $request) }}"
                                       class="inline-flex rounded-md bg-indigo-50 px-2 py-1.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Belum ada pengajuan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="border-t border-slate-200 px-4 py-3 text-xs">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
