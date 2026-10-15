<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold text-white">Alur Approval Pengajuan Custom</h1>
            <p class="text-[10px] text-white/80">Urutan tanda tangan pada Formulir Pengajuan Barang (FPB). Berlaku sama untuk semua pengajuan custom.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-3">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-[11px] leading-4 text-sky-800">
            <p class="font-semibold">Cara kerja</p>
            <p>Kotak "Diajukan oleh" (PIC) dan "Diketahui oleh" atasan langsung diisi pembuat pengajuan untuk tiap FPB dan hanya ditandatangani basah, jadi tidak diatur di sini. Atur di sini tahap yang tetap untuk semua pengajuan, misalnya Spv. GA lalu Manager HCGA. Tahap berjalan berurutan dari atas ke bawah dan disetujui di sistem; tahap terakhir yang menyetujui menyelesaikan pengajuan. Perubahan hanya berlaku untuk pengajuan baru, pengajuan yang sudah berjalan tidak berubah.</p>
            <p>Yang boleh memproses sebuah tahap adalah user yang berjabatan sama (dan memiliki akses ke entitas pengajuan), serta super admin. User penyetuju juga perlu role Approver agar dapat membuka menu Approval.</p>
        </div>

        @if ($steps->where('is_active', true)->isEmpty())
            <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-800">
                Belum ada tahap aktif. Selama kosong, pengajuan custom memakai approval satu tahap seperti biasa.
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Urutan</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Label</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Jabatan</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Aktif</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($steps as $step)
                            <tr>
                                <td class="whitespace-nowrap px-3 py-2">
                                    <div class="flex items-center gap-1">
                                        <span class="w-5 text-xs font-bold text-slate-700">{{ $step->step_order }}</span>

                                        <form method="POST" action="{{ route('approval-steps.move', $step) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="direction" value="up">
                                            <button title="Naikkan" @disabled($loop->first)
                                                    class="rounded border border-slate-200 px-1.5 py-0.5 text-[10px] text-slate-600 hover:bg-slate-100 disabled:opacity-30">▲</button>
                                        </form>

                                        <form method="POST" action="{{ route('approval-steps.move', $step) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="direction" value="down">
                                            <button title="Turunkan" @disabled($loop->last)
                                                    class="rounded border border-slate-200 px-1.5 py-0.5 text-[10px] text-slate-600 hover:bg-slate-100 disabled:opacity-30">▼</button>
                                        </form>
                                    </div>
                                </td>

                                <td class="px-3 py-2">
                                    <select name="label" form="step-{{ $step->id }}"
                                            class="block w-full rounded-md border-slate-300 px-2 py-1 text-xs">
                                        @foreach ($labels as $label)
                                            <option value="{{ $label }}" @selected($step->label === $label)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="px-3 py-2">
                                    <select name="position_id" form="step-{{ $step->id }}"
                                            class="block w-full rounded-md border-slate-300 px-2 py-1 text-xs">
                                        @foreach ($positions as $position)
                                            <option value="{{ $position->id }}" @selected($step->position_id === $position->id)>{{ $position->name }}</option>
                                        @endforeach

                                        @if ($step->position && ! $positions->contains('id', $step->position_id))
                                            <option value="{{ $step->position_id }}" selected>{{ $step->position->name }} (nonaktif)</option>
                                        @endif
                                    </select>

                                    <p class="mt-0.5 text-[10px] {{ ($step->position?->users_count ?? 0) > 0 ? 'text-slate-500' : 'text-amber-600' }}">
                                        {{ $step->position?->users_count ?? 0 }} user berjabatan ini
                                    </p>
                                </td>

                                <td class="px-3 py-2 text-center">
                                    <form method="POST" action="{{ route('approval-steps.update', $step) }}" id="step-{{ $step->id }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <input type="checkbox" name="is_active" value="1" form="step-{{ $step->id }}"
                                           @checked($step->is_active)
                                           class="rounded border-slate-300 text-emerald-600">
                                </td>

                                <td class="whitespace-nowrap px-3 py-2 text-right">
                                    <button form="step-{{ $step->id }}"
                                            class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-200">
                                        Simpan
                                    </button>

                                    <form method="POST" action="{{ route('approval-steps.destroy', $step) }}" class="inline"
                                          onsubmit="return confirm('Hapus tahap ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-md bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700 hover:bg-red-100">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-xs text-slate-500">Belum ada tahap approval.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="POST" action="{{ route('approval-steps.store') }}" class="grid grid-cols-1 gap-2 sm:grid-cols-4 sm:items-end">
                @csrf

                <div>
                    <label class="mb-1 block text-[11px] font-semibold text-slate-700">Label</label>
                    <select name="label" required class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        @foreach ($labels as $label)
                            <option value="{{ $label }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-[11px] font-semibold text-slate-700">Jabatan</label>
                    <select name="position_id" required class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}">{{ $position->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                    Tambah Tahap
                </button>
            </form>

            @if ($positions->isEmpty())
                <p class="mt-2 text-[10px] text-amber-600">Belum ada jabatan aktif. Tambahkan dulu di Master Jabatan.</p>
            @endif
        </div>
    </div>
</x-app-layout>
