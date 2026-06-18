<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Approval Pengajuan ATK/RTK
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Kelola persetujuan pengajuan barang berdasarkan entitas ANR/MAA.
            </p>
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
                    Pengajuan Menunggu Approval
                </h2>
                <p class="text-[10px] text-slate-500">
                    Daftar pengajuan yang masih berstatus pending.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Nomor</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Pemohon</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Tanggal</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Jumlah Item</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Status</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($pendingRequests as $request)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $request->request_number }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                        {{ $request->entity->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-700">
                                    {{ $request->user->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $request->request_date ? $request->request_date->format('d/m/Y') : '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-center text-[11px] font-semibold text-slate-700">
                                    {{ $request->details->count() }} item
                                </td>

                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                        Pending
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-center">
                                    <a href="{{ route('approvals.show', $request) }}"
                                       class="inline-flex rounded-md bg-indigo-50 px-2 py-1.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100">
                                        Proses
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Tidak ada pengajuan pending.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-xs font-semibold text-slate-800">
                    Riwayat Approval
                </h2>
                <p class="text-[10px] text-slate-500">
                    Daftar pengajuan yang sudah diproses.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Nomor</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Pemohon</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Approver</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Tanggal Proses</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Status</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($approvalHistories as $request)
                            @php
                                $statusClass = $request->status === 'approved'
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                    : 'bg-red-50 text-red-700 ring-red-200';

                                $statusText = $request->status === 'approved'
                                    ? 'Disetujui'
                                    : 'Ditolak';
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $request->request_number }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                        {{ $request->entity->code ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-700">
                                    {{ $request->user->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-700">
                                    {{ $request->approver->name ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $request->approved_at ? $request->approved_at->format('d/m/Y H:i') : '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 text-center">
                                    <a href="{{ route('approvals.show', $request) }}"
                                       class="inline-flex rounded-md bg-slate-50 px-2 py-1.5 text-[10px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-100">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Belum ada riwayat approval.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>