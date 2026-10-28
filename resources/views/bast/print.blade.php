<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BAST {{ $pengajuan->bast_number }}</title>

    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #111; }
        .toolbar { display: flex; gap: 8px; justify-content: center; padding: 10px; background: #1f2937; }
        .toolbar button { padding: 6px 16px; border: 0; border-radius: 4px; background: #059669; color: #fff; font-size: 12px; font-weight: bold; cursor: pointer; }
        .toolbar .secondary { background: #4b5563; }
        .sheet { width: 186mm; margin: 10px auto; padding: 8mm; background: #fff; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; border: 1px solid #000; padding: 4px 8px; }
        .header .logo { width: 50mm; text-align: center; }
        .header .logo img { width: 42mm; height: auto; }
        .header .title { text-align: center; font-weight: bold; }
        .header .title .company { font-size: 11pt; }
        .header .title .form-name { margin-top: 2px; font-size: 11pt; }
        .intro { margin: 5mm 0 3mm; line-height: 1.5; font-size: 10pt; }
        .info { margin-bottom: 3mm; font-size: 9.5pt; }
        .info td { padding: 1.5px 4px; vertical-align: top; }
        .info .key { width: 38mm; white-space: nowrap; }
        .items th, .items td { border: 1px solid #000; padding: 3px 6px; vertical-align: top; font-size: 9pt; }
        .items thead th { background: #e6edc9; text-align: center; }
        .items .center { text-align: center; }
        .items .muted { display: block; color: #444; font-size: 8pt; }
        .items .total td { font-weight: bold; background: #f6f8ea; }
        .note { margin-top: 3mm; font-size: 9pt; }
        .closing { margin: 4mm 0 2mm; font-size: 9.5pt; }
        .signatures { margin-top: 3mm; page-break-inside: avoid; }
        .signatures td { width: 50%; border: 1px solid #000; text-align: center; vertical-align: top; font-size: 9pt; }
        .signatures .label { padding: 3px 4px; background: #e6edc9; font-weight: bold; }
        .signatures .position { padding: 3px 4px; height: 8mm; border-top: 0; border-bottom: 0; font-size: 8.5pt; }
        .signatures .space { height: 24mm; border-top: 0; border-bottom: 0; vertical-align: middle; }
        .signatures .space img { display: block; max-width: 56mm; max-height: 22mm; margin: 0 auto; }
        .signatures .person { padding: 3px 4px; height: 11mm; border-top: 0; font-weight: bold; }
        .signatures .person small { display: block; margin-top: 1px; font-weight: normal; font-size: 8pt; }
        .footnote { margin-top: 3mm; font-size: 8pt; color: #555; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { width: auto; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    @php
        $fmt = fn ($time) => $time ? $time->copy()->timezone(config('app.timezone'))->locale('id')->translatedFormat('d F Y') : '-';
        $company = \Illuminate\Support\Str::upper($pengajuan->entity?->name ?? 'PT Agrofarm Nusa Raya');
        $handedAt = $pengajuan->fulfillments->firstWhere('stage', 'handed_over')?->happened_at;
        $receiverName = $pengajuan->received_name ?: $pengajuan->receiverName();
    @endphp

    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Cetak</button>
        <button type="button" class="secondary" onclick="window.close()">Tutup</button>
    </div>

    <div class="sheet">
        <table class="header">
            <tr>
                <td class="logo"><img src="{{ asset('images/agrofarm-logo.png') }}" alt="Agrofarm"></td>
                <td class="title">
                    <div class="company">{{ $company }}</div>
                    <div class="form-name">BERITA ACARA SERAH TERIMA BARANG (BAST)</div>
                </td>
            </tr>
        </table>

        <p class="intro">
            Pada hari ini, tanggal {{ $fmt($handedAt ?? $pengajuan->received_at) }}, telah dilakukan serah terima barang
            hasil pengadaan sebagaimana pengajuan berikut:
        </p>

        <table class="info">
            <tr><td class="key">No. BAST</td><td>: {{ $pengajuan->bast_number }}</td></tr>
            <tr><td class="key">Ref. No. Pengajuan (FPB)</td><td>: {{ $pengajuan->request_number }}</td></tr>
            <tr><td class="key">Divisi/Departemen</td><td>: {{ $pengajuan->department ?: '-' }}</td></tr>
            <tr><td class="key">Tanggal Serah Terima</td><td>: {{ $fmt($handedAt) }}</td></tr>
            <tr><td class="key">Tanggal Diterima</td><td>: {{ $fmt($pengajuan->received_at) }}</td></tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 10mm;">No</th>
                    <th>Nama Barang</th>
                    <th style="width: 45mm;">Type / Merk</th>
                    <th style="width: 18mm;">Jumlah</th>
                    <th style="width: 18mm;">Satuan</th>
                    <th style="width: 40mm;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pengajuan->details as $detail)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $detail->custom_item_name }}</td>
                        <td>
                            {{ $detail->brand_type }}
                            @if ($detail->specification)
                                <span class="muted">{{ \Illuminate\Support\Str::limit($detail->specification, 120) }}</span>
                            @endif
                        </td>
                        <td class="center">{{ number_format($detail->approved_quantity ?? $detail->quantity, 0, ',', '.') }}</td>
                        <td class="center">{{ $detail->unit }}</td>
                        <td>{{ $detail->note }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="3" class="center">TOTAL</td>
                    <td class="center">{{ number_format($pengajuan->details->sum(fn ($d) => $d->approved_quantity ?? $d->quantity), 0, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        @if ($pengajuan->bast_note)
            <p class="note"><strong>Kondisi / catatan:</strong> {{ $pengajuan->bast_note }}</p>
        @endif

        <p class="closing">
            Barang telah diterima dalam keadaan baik dan lengkap oleh pihak penerima, dan sejak tanggal serah terima
            menjadi tanggung jawab penerima.
        </p>

        <table class="signatures">
            <tr>
                <td class="label">Yang Menyerahkan</td>
                <td class="label">Yang Menerima</td>
            </tr>
            <tr>
                <td class="position">{{ $handedBy?->position?->name ?? 'Admin' }}</td>
                <td class="position">{{ $pengajuan->pic_name ? ($pengajuan->pic_position ?? '') : ($pengajuan->user?->position?->name ?? '') }}</td>
            </tr>
            <tr>
                <td class="space">
                    @if ($handedImage)
                        <img src="{{ $handedImage }}" alt="Tanda tangan penyerah">
                    @endif
                </td>
                <td class="space">
                    @if ($receivedImage)
                        <img src="{{ $receivedImage }}" alt="Tanda tangan penerima">
                    @endif
                </td>
            </tr>
            <tr>
                <td class="person">
                    {{ $handedBy?->name }}
                    <small>{{ $fmt($handedAt) }}</small>
                </td>
                <td class="person">
                    {{ $receiverName }}
                    <small>
                        {{ $fmt($pengajuan->received_at) }}
                        @if ($pengajuan->received_on_behalf)
                            &middot; dikonfirmasi admin atas nama penerima
                        @endif
                    </small>
                </td>
            </tr>
        </table>

        <p class="footnote no-print">
            Tanda tangan digital dan tanggal tercetak otomatis dari konfirmasi di sistem. Kolom tanpa tanda tangan digital dapat ditandatangani langsung setelah dicetak.
        </p>
    </div>
</body>
</html>
