<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bukti Pengeluaran {{ $stockOut->issue_number }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #e5e7eb;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #111;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            padding: 10px;
            background: #1f2937;
        }

        .toolbar button,
        .toolbar a {
            padding: 6px 16px;
            border: 0;
            border-radius: 4px;
            background: #059669;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar .secondary { background: #4b5563; }

        .flash {
            max-width: 190mm;
            margin: 10px auto 0;
            padding: 8px 12px;
            border: 1px solid #a7f3d0;
            border-radius: 4px;
            background: #ecfdf5;
            color: #065f46;
            font-size: 12px;
        }

        .sheet {
            width: 190mm;
            margin: 10px auto;
            padding: 8mm;
            background: #fff;
        }

        table { width: 100%; border-collapse: collapse; }

        .header td {
            vertical-align: middle;
            border: 1px solid #000;
            padding: 6px 10px;
        }

        .header .logo { width: 58mm; text-align: center; }
        .header .logo img { width: 48mm; height: auto; }
        .header .title { text-align: center; font-weight: bold; }
        .header .title .company { font-size: 11pt; }
        .header .title .form-name { margin-top: 3px; font-size: 12pt; }

        .meta { margin-top: 4mm; }
        .meta td { padding: 2px 4px; font-size: 9.5pt; vertical-align: top; }
        .meta .key { width: 36mm; white-space: nowrap; }

        .items { margin-top: 4mm; }
        .items th,
        .items td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 9.5pt;
            vertical-align: top;
        }
        .items thead th { background: #e6edc9; text-align: center; }
        .items .center { text-align: center; }
        .items .total td { font-weight: bold; background: #f6f8ea; }

        .signatures { margin-top: 6mm; page-break-inside: avoid; }
        .signatures td {
            width: 50%;
            border: 1px solid #000;
            text-align: center;
            vertical-align: top;
            font-size: 9.5pt;
        }
        .signatures .label { padding: 4px; background: #e6edc9; font-weight: bold; }
        .signatures .position { padding: 3px 4px; height: 8mm; border-top: 0; border-bottom: 0; font-size: 9pt; }
        .signatures .space { height: 24mm; border-top: 0; border-bottom: 0; }
        .signatures .person { padding: 4px; height: 12mm; border-top: 0; font-weight: bold; }
        .signatures .person small { display: block; margin-top: 1px; font-weight: normal; font-size: 8pt; }

        .footnote { margin-top: 4mm; font-size: 8pt; color: #555; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { width: auto; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    @php
        $company = \Illuminate\Support\Str::upper($stockOut->entity?->name ?? 'PT Agrofarm Nusa Raya');
        $date = $stockOut->issue_date?->copy()->locale('id')->translatedFormat('d F Y') ?? '-';
    @endphp

    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Cetak Bukti</button>
        <a href="{{ route('stock-outs.create') }}">Catat Lagi</a>
        <a href="{{ route('stock-outs.index') }}" class="secondary">Kembali ke Daftar</a>
    </div>

    @if (session('success'))
        <div class="flash no-print">{{ session('success') }}</div>
    @endif

    <div class="sheet">
        <table class="header">
            <tr>
                <td class="logo">
                    <img src="{{ asset('images/agrofarm-logo.png') }}" alt="Agrofarm">
                </td>

                <td class="title">
                    <div class="company">{{ $company }}</div>
                    <div class="form-name">BUKTI PENGELUARAN BARANG</div>
                </td>
            </tr>
        </table>

        <table class="meta">
            <tr>
                <td class="key">Nomor</td>
                <td>: {{ $stockOut->issue_number }}</td>
                <td class="key">Penerima</td>
                <td>: {{ $stockOut->recipient_name }}</td>
            </tr>
            <tr>
                <td class="key">Tanggal</td>
                <td>: {{ $date }}</td>
                <td class="key">Divisi/Departemen</td>
                <td>: {{ $stockOut->recipient_department ?: '-' }}</td>
            </tr>
            <tr>
                <td class="key">Gudang Asal</td>
                <td>: {{ $stockOut->warehouse?->name ?? '-' }}</td>
                <td class="key">Keterangan</td>
                <td>: {{ $stockOut->note ?: '-' }}</td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 10mm;">No</th>
                    <th style="width: 32mm;">Kode</th>
                    <th>Nama Barang</th>
                    <th style="width: 22mm;">Jumlah</th>
                    <th style="width: 22mm;">Satuan</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($stockOut->items as $line)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $line->item?->code ?? '-' }}</td>
                        <td>{{ $line->item?->name ?? '-' }}</td>
                        <td class="center">{{ number_format($line->quantity, 0, ',', '.') }}</td>
                        <td class="center">{{ $line->item?->unit ?? '-' }}</td>
                    </tr>
                @endforeach

                <tr class="total">
                    <td colspan="3" class="center">TOTAL BARANG KELUAR</td>
                    <td class="center">{{ number_format($totalQuantity, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <table class="signatures">
            <tr>
                <td class="label">Diserahkan oleh</td>
                <td class="label">Diterima oleh</td>
            </tr>
            <tr>
                <td class="position">{{ $stockOut->issuer?->position?->name ?? 'Admin Gudang ' . ($stockOut->warehouse?->name ?? '') }}</td>
                <td class="position">{{ $stockOut->recipient_department }}</td>
            </tr>
            <tr>
                <td class="space"></td>
                <td class="space"></td>
            </tr>
            <tr>
                <td class="person">
                    {{ $stockOut->issuer?->name }}
                    <small>{{ $stockOut->created_at?->format('d/m/Y') }}</small>
                </td>
                <td class="person">{{ $stockOut->recipient_name }}</td>
            </tr>
        </table>

        <p class="footnote">
            Dokumen ini dibuat otomatis oleh sistem. Stok gudang sudah berkurang sesuai jumlah di atas.
            Tanda tangan dibubuhkan langsung pada kolom kosong setelah dicetak.
        </p>
    </div>
</body>
</html>
