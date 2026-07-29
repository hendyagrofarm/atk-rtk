<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-sm font-semibold text-white">
                    Audit & Penyesuaian Stok
                </h1>
                <p class="text-[10px] text-white/80">
                    Riwayat pemeriksaan stok fisik dan penyesuaian stok sistem.
                </p>
            </div>

            <a href="{{ route('stock-audits.create') }}"
   class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-emerald-700">
    <svg
        class="mr-1.5 h-4 w-4"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        viewBox="0 0 24 24"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 5v14m7-7H5"
        />
    </svg>

    Buat Audit Stok
</a>
        </div>
    </x-slot>

    <div class="space-y-3">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET"
                  action="{{ route('stock-audits.index') }}"
                  class="space-y-3">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-5">
                    <input type="date"
                           name="start_date"
                           value="{{ request('start_date') }}"
                           class="rounded-md border-slate-300 px-3 py-1.5 text-xs">

                    <input type="date"
                           name="end_date"
                           value="{{ request('end_date') }}"
                           class="rounded-md border-slate-300 px-3 py-1.5 text-xs">

                    <select name="entity_id"
                            class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Entitas Akses</option>
                        @foreach ($entities as $entity)
                            <option value="{{ $entity->id }}"
                                    @selected(
                                        (string) request('entity_id')
                                        === (string) $entity->id
                                    )>
                                {{ $entity->code }} - {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="warehouse_id"
                            class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Semua Gudang Akses</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}"
                                    @selected(
                                        (string) request('warehouse_id')
                                        === (string) $warehouse->id
                                    )>
                                {{ $warehouse->code }} - {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>

                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="No audit, barang, alasan"
                           class="rounded-md border-slate-300 px-3 py-1.5 text-xs">
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('stock-audits.index') }}"
                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                        Reset
                    </a>

                    <button type="submit"
                            class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Total Audit</p>
                <p class="mt-1 text-lg font-bold text-slate-800">
                    {{ number_format($totalAudits) }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Total Penambahan</p>
                <p class="mt-1 text-lg font-bold text-emerald-700">
                    +{{ number_format($totalPositive) }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-[10px] text-slate-500">Total Pengurangan</p>
                <p class="mt-1 text-lg font-bold text-red-700">
                    -{{ number_format($totalNegative) }}
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach ([
                                'No',
                                'Nomor Audit',
                                'Tanggal',
                                'Entitas',
                                'Gudang',
                                'Barang',
                                'Stok Sistem',
                                'Stok Fisik',
                                'Selisih',
                                'Alasan',
                                'Petugas'
                            ] as $heading)
                                <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($audits as $audit)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $audits->firstItem() + $loop->index }}
                                </td>

                                <td class="px-4 py-2.5 text-[10px] font-semibold text-slate-700">
                                    {{ $audit->audit_number }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $audit->audit_date?->format('d/m/Y') ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] font-semibold text-blue-700">
                                    {{ $audit->entity?->code ?? '-' }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-[11px] font-semibold text-slate-700">
                                        {{ $audit->warehouse?->name ?? '-' }}
                                    </p>
                                    <p class="text-[9px] text-slate-500">
                                        {{ $audit->warehouse?->code ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-4 py-2.5">
                                    <p class="text-xs font-semibold text-slate-800">
                                        {{ $audit->item?->name ?? '-' }}
                                    </p>
                                    <p class="text-[10px] text-slate-500">
                                        {{ $audit->item?->code ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">
                                    {{ number_format($audit->system_stock) }}
                                </td>

                                <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-800">
                                    {{ number_format($audit->physical_stock) }}
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    @if ($audit->difference > 0)
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                            {{ $audit->difference_label }}
                                        </span>
                                    @elseif ($audit->difference < 0)
                                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700">
                                            {{ $audit->difference_label }}
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                            0
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $audit->reason }}
                                </td>

                                <td class="px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $audit->user?->name ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11"
                                    class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    Data audit stok belum tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($audits->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $audits->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
