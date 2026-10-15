<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold text-white">Master Jabatan</h1>
            <p class="text-[10px] text-white/80">Jabatan dipakai untuk menentukan siapa yang berwenang pada tiap tahap approval pengajuan custom.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-3">
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

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="POST" action="{{ route('positions.store') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                @csrf

                <div class="flex-1">
                    <label for="name" class="mb-1 block text-[11px] font-semibold text-slate-700">Tambah Jabatan</label>
                    <input type="text" name="name" id="name" maxlength="150" required
                           value="{{ old('name') }}"
                           placeholder="Contoh: Spv. GA"
                           class="block w-full rounded-md border-slate-300 px-3 py-1.5 text-xs">
                </div>

                <button class="rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800">
                    Tambah
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase text-slate-500">Jabatan</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">User</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Tahap</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-bold uppercase text-slate-500">Aktif</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-bold uppercase text-slate-500">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($positions as $position)
                            <tr>
                                <td class="px-3 py-2">
                                    <form method="POST" action="{{ route('positions.update', $position) }}" id="position-{{ $position->id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $position->name }}" maxlength="150" required
                                               class="block w-full rounded-md border-slate-300 px-2 py-1 text-xs">
                                    </form>
                                </td>
                                <td class="px-3 py-2 text-right text-xs">{{ $position->users_count }}</td>
                                <td class="px-3 py-2 text-right text-xs">{{ $position->approval_steps_count }}</td>
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" name="is_active" value="1" form="position-{{ $position->id }}"
                                           @checked($position->is_active)
                                           class="rounded border-slate-300 text-emerald-600">
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">
                                    <button form="position-{{ $position->id }}"
                                            class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-200">
                                        Simpan
                                    </button>

                                    <form method="POST" action="{{ route('positions.destroy', $position) }}" class="inline"
                                          onsubmit="return confirm('Hapus jabatan ini?')">
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
                                <td colspan="5" class="px-4 py-8 text-center text-xs text-slate-500">
                                    Belum ada jabatan. Tambahkan jabatan seperti Head of Corporate Affair &amp; Legal, Spv. GA, dan Manager HCGA.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-[10px] leading-4 text-slate-500">
            Jabatan user diatur di menu Manajemen User. Jabatan yang masih dipakai user atau alur approval tidak dapat dihapus.
        </p>
    </div>
</x-app-layout>
