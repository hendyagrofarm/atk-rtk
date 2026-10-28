<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FPB {{ $pengajuan->request_number }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e5e7eb;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #111;
        }

        .toolbar {
            display: flex;
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

        .toolbar .secondary {
            background: #4b5563;
        }

        .sheet {
            width: 277mm;
            margin: 10px auto;
            padding: 8mm;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: middle;
            border: 1px solid #000;
            padding: 4px 8px;
        }

        .header .logo {
            width: 62mm;
            text-align: center;
        }

        .header .logo img {
            width: 52mm;
            height: auto;
        }

        .header .title {
            text-align: center;
        }

        .header .title .company {
            font-size: 11pt;
            font-weight: bold;
        }

        .header .title .form-name {
            margin-top: 2px;
            font-size: 11pt;
            font-weight: bold;
        }

        .header .meta {
            width: 78mm;
            padding: 0;
        }

        .header .meta table td {
            border: 0;
            border-bottom: 1px solid #000;
            padding: 3px 6px;
            font-size: 9pt;
        }

        .header .meta table tr:last-child td {
            border-bottom: 0;
        }

        .header .meta .key {
            width: 30mm;
            white-space: nowrap;
        }

        .items {
            margin-top: 3mm;
        }

        .items th,
        .items td {
            border: 1px solid #000;
            padding: 3px 6px;
            vertical-align: top;
            font-size: 9pt;
        }

        .items thead th {
            background: #e6edc9;
            text-align: center;
            font-weight: bold;
        }

        .items .section td {
            background: #e6edc9;
            font-weight: bold;
            text-align: center;
        }

        .items .group td {
            font-weight: bold;
        }

        .items .center {
            text-align: center;
        }

        .items .muted {
            display: block;
            margin-top: 1px;
            color: #444;
            font-size: 8pt;
        }

        .items .blank td {
            height: 6mm;
        }

        .items .total td {
            font-weight: bold;
            background: #f6f8ea;
        }

        .signatures {
            width: auto;
            margin-top: 4mm;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 55mm;
            border: 1px solid #000;
            text-align: center;
            vertical-align: top;
            font-size: 9pt;
        }

        .signatures .label {
            padding: 3px 4px;
            background: #e6edc9;
            font-weight: bold;
        }

        .signatures .position {
            padding: 3px 4px;
            height: 10mm;
            border-top: 0;
            border-bottom: 0;
            font-size: 8.5pt;
        }

        .signatures .space {
            height: 22mm;
            border-top: 0;
            border-bottom: 0;
            vertical-align: middle;
        }

        .signatures .space img {
            display: block;
            max-width: 46mm;
            max-height: 20mm;
            margin: 0 auto;
        }

        .signatures .person {
            padding: 3px 4px;
            height: 11mm;
            border-top: 0;
            font-weight: bold;
        }

        .signatures .person small {
            display: block;
            margin-top: 1px;
            font-weight: normal;
            font-size: 8pt;
        }

        .signatures .rejected {
            color: #b91c1c;
        }

        .footnote {
            margin-top: 3mm;
            font-size: 8pt;
            color: #555;
        }

        @media print {
            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                width: auto;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    @php
        $date = $pengajuan->request_date?->copy()->locale('id')->translatedFormat('d F Y') ?? '-';
        $company = \Illuminate\Support\Str::upper($pengajuan->entity?->name ?? 'PT Agrofarm Nusa Raya');
        $letter = fn (int $index) => chr(65 + ($index % 26));
    @endphp

    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Cetak</button>
        <button type="button" class="secondary" onclick="window.close()">Tutup</button>
    </div>

    <div class="sheet">
        <table class="header">
            <tr>
                <td class="logo">
                    <img src="{{ asset('images/agrofarm-logo.png') }}" alt="Agrofarm">
                </td>

                <td class="title">
                    <div class="company">{{ $company }}</div>
                    <div class="form-name">FORMULIR PENGAJUAN BARANG (FPB)</div>
                </td>

                <td class="meta">
                    <table>
                        <tr>
                            <td class="key">Tanggal Pengajuan</td>
                            <td>: {{ $date }}</td>
                        </tr>
                        <tr>
                            <td class="key">Divisi/Departemen</td>
                            <td>: {{ $pengajuan->department ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="key">No. Pengajuan</td>
                            <td>: {{ $pengajuan->request_number }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 10mm;">No</th>
                    <th style="width: 62mm;">Nama Barang</th>
                    <th style="width: 40mm;">Type/ Merk</th>
                    <th style="width: 16mm;">Jumlah</th>
                    <th style="width: 18mm;">Satuan</th>
                    <th>Digunakan Untuk</th>
                    <th style="width: 55mm;">Keterangan</th>
                </tr>
            </thead>

            <tbody>
                {{-- Hanya bagian yang diajukan yang ditampilkan. --}}
                @if (count($sections['opex']) > 0)
                    <tr class="section"><td colspan="7">OPEX</td></tr>

                    @foreach ($sections['opex'] as $groupIndex => $group)
                        <tr class="group">
                            <td class="center">{{ $letter($groupIndex) }}</td>
                            <td colspan="6">{{ $group['label'] }}</td>
                        </tr>

                        @foreach ($group['items'] as $detail)
                            @include('fpb._row', ['detail' => $detail, 'number' => $loop->iteration])
                        @endforeach
                    @endforeach
                @endif

                @if ($sections['capex']->isNotEmpty())
                    <tr class="section"><td colspan="7">CAPEX</td></tr>

                    @foreach ($sections['capex'] as $detail)
                        @include('fpb._row', ['detail' => $detail, 'number' => $letter($loop->index)])
                    @endforeach
                @endif

                <tr class="total">
                    <td colspan="3" class="center">TOTAL PENGAJUAN</td>
                    <td class="center">{{ number_format($totalQuantity, 0, ',', '.') }}</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>

        <table class="signatures">
            <tr>
                @foreach ($signatures as $signature)
                    <td class="label">{{ $signature['label'] }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($signatures as $signature)
                    <td class="position">{{ $signature['position'] }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($signatures as $signature)
                    <td class="space">
                        @if (! empty($signature['image']))
                            <img src="{{ $signature['image'] }}" alt="Tanda tangan {{ $signature['name'] }}">
                        @endif
                    </td>
                @endforeach
            </tr>
            <tr>
                @foreach ($signatures as $signature)
                    <td class="person {{ $signature['note'] ? 'rejected' : '' }}">
                        {{ $signature['name'] ?? '' }}
                        @if ($signature['name'] && ($signature['date'] || $signature['note']))
                            <small>{{ $signature['note'] ? $signature['note'] . ($signature['date'] ? ', ' : '') : '' }}{{ $signature['date'] }}</small>
                        @endif
                    </td>
                @endforeach
            </tr>
        </table>

        <p class="footnote no-print">
            Tanda tangan digital, nama, dan tanggal tercetak otomatis untuk tahap yang disetujui di sistem. Kolom yang masih kosong ditandatangani langsung setelah dicetak.
        </p>
    </div>
</body>
</html>
