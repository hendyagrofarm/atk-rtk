<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold text-white">Tambah Entitas</h1>
                <p class="text-[10px] text-white/80">Tambahkan entitas perusahaan baru.</p>
            </div>

            <a href="{{ route('entities.index') }}"
               class="rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-white/20">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <form method="POST" action="{{ route('entities.store') }}">
                <div class="p-4">
                    @include('entities._form')
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <a href="{{ route('entities.index') }}"
                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">
                        Batal
                    </a>

                    <button type="submit"
                            class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-700">
                        Simpan Entitas
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
