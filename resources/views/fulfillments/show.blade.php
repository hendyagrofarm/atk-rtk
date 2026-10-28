<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold leading-5 text-white">Pembelanjaan {{ $pengajuan->request_number }}</h1>
                <p class="text-[10px] leading-4 text-white/80">Perbarui tahap pembelian sampai barang diterima pengguna.</p>
            </div>

            <a href="{{ route('fulfillments.index') }}"
               class="rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white">
                Kembali
            </a>
        </div>
    </x-slot>

    @php
        $stages = \App\Models\RequestFulfillment::STAGES;
        $current = \App\Support\Fulfillment::currentStage($pengajuan);
        $reachedIndex = $current ? array_search($current, array_keys($stages), true) : -1;
        $awaitingReceipt = \App\Support\Fulfillment::isAwaitingReceipt($pengajuan);
        $complete = \App\Support\Fulfillment::isComplete($pengajuan);
        $receiverUser = $pengajuan->receiverUser();
        $inputClass = 'mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-xs focus:border-[#2C87B8] focus:ring-[#2C87B8]';
    @endphp

    <div class="space-y-3">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $errors->first() }}</div>
        @endif

        {{-- Ringkasan --}}
        <div class="rounded-md border border-[#D9E1E7] bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 text-xs md:grid-cols-4">
                <div>
                    <p class="text-[10px] text-slate-500">PIC / Penerima</p>
                    <p class="font-semibold text-slate-800">{{ $pengajuan->receiverName() }}</p>
                    <p class="text-[10px] text-slate-400">{{ $receiverUser ? 'Punya akun' : 'Tanpa akun (diisi manual)' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-slate-500">Departemen</p>
                    <p class="font-semibold text-slate-800">{{ $pengajuan->department ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-slate-500">Disetujui</p>
                    <p class="font-semibold text-slate-800">{{ \App\Support\RequestTimeline::formatTime($pengajuan->approved_at) ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-slate-500">No. BAST</p>
                    <p class="font-semibold text-slate-800">{{ $pengajuan->bast_number ?: '-' }}</p>
                </div>
            </div>

            <ul class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-xs text-slate-700">
                @foreach ($pengajuan->details as $detail)
                    <li>{{ $loop->iteration }}. {{ $detail->custom_item_name }}
                        <span class="text-slate-400">&middot; {{ number_format($detail->quantity, 0, ',', '.') }} {{ $detail->unit }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('fpb.print', $pengajuan->id) }}" target="_blank"
                   class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">Cetak FPB</a>
                @if ($complete)
                    <a href="{{ route('bast.print', $pengajuan->id) }}" target="_blank"
                       class="rounded-md bg-[#08B05A] px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-600">Cetak BAST</a>
                @endif
            </div>
        </div>

        {{-- Langkah --}}
        <div class="rounded-md border border-[#D9E1E7] bg-white p-4 shadow-sm">
            <ol class="grid grid-cols-1 gap-2 sm:grid-cols-5">
                @foreach (array_keys($stages) as $i => $key)
                    <li class="rounded-md border px-2 py-2 text-center text-[10px] font-semibold
                        {{ $i <= $reachedIndex ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400' }}">
                        {{ $i + 1 }}. {{ $stages[$key] }}
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Form tahap berikutnya --}}
        @if ($nextStage)
            <form method="POST" action="{{ route('fulfillments.advance', $pengajuan) }}" class="rounded-md border border-[#D9E1E7] bg-white p-4 shadow-sm">
                @csrf

                <h3 class="text-[13px] font-bold text-slate-800">Catat tahap: {{ $stages[$nextStage] }}</h3>
                <p class="mb-3 text-[10px] text-slate-500">Waktu dicatat otomatis saat disimpan. Tahap tidak dapat dilompati.</p>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @if ($nextStage === 'ordered')
                        <label class="text-[11px] font-semibold text-slate-600">Vendor / Toko
                            <input type="text" name="vendor" value="{{ old('vendor') }}" maxlength="150" class="{{ $inputClass }}">
                        </label>
                        <label class="text-[11px] font-semibold text-slate-600">No. PO / Pesanan
                            <input type="text" name="po_number" value="{{ old('po_number') }}" maxlength="100" class="{{ $inputClass }}">
                        </label>
                        <label class="text-[11px] font-semibold text-slate-600">Estimasi Barang Tiba
                            <input type="date" name="eta_date" value="{{ old('eta_date') }}" class="{{ $inputClass }}">
                        </label>
                    @endif

                    @if ($nextStage === 'handed_over')
                        <label class="text-[11px] font-semibold text-slate-600">Nama Penerima
                            <input type="text" name="received_name" value="{{ old('received_name', $pengajuan->receiverName()) }}" maxlength="150" class="{{ $inputClass }}">
                        </label>
                    @endif

                    <label class="text-[11px] font-semibold text-slate-600 {{ $nextStage === 'ordered' ? 'md:col-span-3' : 'md:col-span-2' }}">
                        {{ $nextStage === 'handed_over' ? 'Kondisi / catatan serah terima (tercetak di BAST)' : 'Catatan (opsional)' }}
                        <input type="text" name="note" value="{{ old('note') }}" maxlength="1000" class="{{ $inputClass }}">
                    </label>
                </div>

                @if ($nextStage === 'handed_over' && ! $adminHasSignature)
                    <p class="mt-3 text-[10px] text-amber-700">
                        Anda belum punya tanda tangan digital, kotak "Yang Menyerahkan" pada BAST akan kosong. Unggah tanda tangan di menu Manajemen User.
                    </p>
                @endif

                <button type="submit" class="mt-4 rounded-md bg-[#2C87B8] px-4 py-2 text-[11px] font-bold text-white hover:bg-[#256f96]">
                    Simpan: {{ $stages[$nextStage] }}
                </button>
            </form>
        @elseif ($awaitingReceipt)
            <div class="rounded-md border border-sky-200 bg-sky-50 p-4 text-xs text-sky-800">
                <p class="font-semibold">Menunggu {{ $pengajuan->received_name ?: $pengajuan->receiverName() }} mengonfirmasi barang diterima.</p>

                @if ($receiverUser)
                    <p class="mt-1 text-[11px]">Penerima punya akun dan mengonfirmasi dari halaman pengajuannya (Pengajuan Saya).</p>
                @else
                    <form method="POST" action="{{ route('fulfillments.receive', $pengajuan) }}" class="mt-3 space-y-2">
                        @csrf
                        <p class="text-[11px]">Penerima tidak punya akun. Setelah BAST ditandatangani langsung, konfirmasi atas namanya:</p>
                        <input type="text" name="note" maxlength="1000" placeholder="Catatan (opsional)" class="{{ $inputClass }}">
                        <button type="submit" class="rounded-md bg-[#2C87B8] px-4 py-2 text-[11px] font-bold text-white hover:bg-[#256f96]">
                            Konfirmasi Diterima atas Nama Penerima
                        </button>
                    </form>
                @endif
            </div>
        @elseif ($complete)
            <div class="rounded-md border border-emerald-200 bg-emerald-50 p-4 text-xs text-emerald-800">
                Barang sudah diterima {{ $pengajuan->received_name }} pada {{ \App\Support\RequestTimeline::formatTime($pengajuan->received_at) }}.
            </div>
        @endif

        {{-- Lacak --}}
        <div class="overflow-hidden rounded-md border border-[#D9E1E7] bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-xs font-semibold text-slate-800">Lacak Pengajuan</h3>
            </div>
            @include('partials.request-tracking', ['events' => $events])
        </div>
    </div>
</x-app-layout>
