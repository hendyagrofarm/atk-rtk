<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BAST {{ $pengajuan->bast_number }}</title>

    <style>
        @page { size: A4 portrait; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; font-family: "Times New Roman", Times, serif; font-size: 11pt; color: #111; }
        .toolbar { display: flex; gap: 8px; justify-content: center; padding: 10px; background: #1f2937; font-family: Arial, sans-serif; }
        .toolbar button { padding: 6px 16px; border: 0; border-radius: 4px; background: #059669; color: #fff; font-size: 12px; font-weight: bold; cursor: pointer; }
        .toolbar .secondary { background: #4b5563; }
        .sheet { width: 186mm; margin: 10px auto; padding: 8mm; background: #fff; }
        .sheet + .sheet { page-break-before: always; }
        table { width: 100%; border-collapse: collapse; }
        .memo { text-align: center; }
        .memo .small { font-size: 10pt; font-weight: bold; letter-spacing: 1px; }
        .memo h1 { margin: 2mm 0 1mm; font-size: 13pt; text-decoration: underline; }
        .memo .number { font-size: 11pt; }
        .intro, .para { margin: 4mm 0 2mm; line-height: 1.5; text-align: justify; }
        .party { margin: 2mm 0 2mm 8mm; }
        .party td { padding: 0 3px; vertical-align: top; }
        .party .key { width: 22mm; }
        .party .sep { width: 3mm; }
        .items { margin: 3mm 0; }
        .items th, .items td { border: 1px solid #000; padding: 3px 6px; vertical-align: top; font-size: 10.5pt; }
        .items thead th { text-align: center; }
        .items .center { text-align: center; }
        .items .pre { white-space: pre-line; }
        .signatures { margin-top: 5mm; page-break-inside: avoid; }
        .signatures td { width: 33.33%; text-align: center; vertical-align: top; }
        .signatures .place { height: 6mm; font-size: 10.5pt; }
        .signatures .space { height: 24mm; vertical-align: middle; }
        .signatures .space img { display: block; max-width: 48mm; max-height: 22mm; margin: 0 auto; }
        .signatures .name { font-weight: bold; text-decoration: underline; }
        .sheet { position: relative; }
        .kop { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }
        .sheet > *:not(.kop) { position: relative; z-index: 1; }
        .sheet.has-kop { width: 210mm; min-height: 296mm; margin: 10px auto; padding: 42mm 20mm 40mm 26mm; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @media print {
            .sheet.has-kop { margin: 0; }
        }
        .footnote { margin-top: 3mm; font-size: 8pt; color: #555; font-family: Arial, sans-serif; }
        .letter .meta td { padding: 1px 4px; vertical-align: top; }
        .letter .meta .key { width: 28mm; }
        .letter ol { margin: 2mm 0 2mm 6mm; padding-left: 5mm; line-height: 1.45; text-align: justify; }
        .letter .line { display: inline-block; min-width: 70mm; border-bottom: 1px dotted #000; }
        .letter .sign { margin: 4mm 0 0 auto; width: 70mm; text-align: center; }
        .letter .sign .space { height: 22mm; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { width: auto; margin: 0; padding: 0; }
        }
    </style>
    @if ($pengajuan->entity?->letterhead_path)
        <style>@page { size: A4 portrait; margin: 0; }</style>
    @endif
</head>
<body>
    @php
        $id = fn ($date, $format) => $date ? $date->copy()->locale('id')->translatedFormat($format) : '';
        $date = $pengajuan->bast_date ?? now();
        $company = $pengajuan->entity?->name ?? 'PT. Agrofarm Nusa Raya';
        $letterhead = \App\Support\EntityLetterhead::dataUri($pengajuan->entity);
        $totalQty = collect($items)->sum(fn ($row) => (int) $row['quantity']);
        $firstPosition = $bast['first_position'] ?: 'Pihak Pertama';
        $secondName = $bast['second_name'] ?: '…………………';
        $blank = '…………………';
    @endphp

    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Cetak</button>
        <button type="button" class="secondary" onclick="window.close()">Tutup</button>
    </div>

    {{-- Halaman 1: Berita Acara Serah Terima Barang Inventaris --}}
    <div class="sheet {{ $letterhead ? 'has-kop' : '' }}">
        @if ($letterhead)
            <img class="kop" src="{{ $letterhead }}" alt="">
        @endif

        <div class="memo">
            @unless ($letterhead)
                <div class="small">{{ \Illuminate\Support\Str::upper($company) }}</div>
            @endunless
            <div class="small">INTERNAL MEMO</div>
            <h1>BERITA ACARA SERAH TERIMA BARANG INVENTARIS</h1>
            <div class="number">Nomor: {{ $pengajuan->bast_number }}</div>
        </div>

        <p class="intro">
            Pada hari ini, {{ $id($date, 'l') }}, tanggal {{ $id($date, 'j') }} bulan {{ $id($date, 'F') }} tahun {{ $id($date, 'Y') }},
            Kami yang bertanda-tangan di bawah ini:
        </p>

        <table class="party">
            <tr><td class="key">Nama</td><td class="sep">:</td><td>{{ $bast['first_name'] }}</td></tr>
            <tr><td class="key">NIK</td><td class="sep">:</td><td>{{ $bast['first_nik'] }}</td></tr>
            <tr><td class="key">Jabatan</td><td class="sep">:</td><td>{{ $bast['first_position'] }}</td></tr>
            <tr><td colspan="3">Selanjutnya disebut <strong>PIHAK PERTAMA</strong>.</td></tr>
        </table>

        <table class="party">
            <tr><td class="key">Nama</td><td class="sep">:</td><td>{{ $bast['second_name'] }}</td></tr>
            <tr><td class="key">NIK</td><td class="sep">:</td><td>{{ $bast['second_nik'] }}</td></tr>
            <tr><td class="key">Jabatan</td><td class="sep">:</td><td>{{ $bast['second_position'] }}</td></tr>
            <tr><td colspan="3">Selanjutnya disebut <strong>PIHAK KEDUA</strong>.</td></tr>
        </table>

        <p class="para">
            <strong>PIHAK PERTAMA</strong> atas nama {{ $firstPosition }} menyerahkan barang/peralatan penunjang yang siap dipakai
            untuk keperluan pelaksanaan tugas-tugas di {{ $company }}, dengan rincian sebagai berikut:
        </p>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 10mm;">No.</th>
                    <th>Nama Barang</th>
                    <th>Spesifikasi</th>
                    <th style="width: 30mm;">Kode Asset</th>
                    <th style="width: 18mm;">Jumlah</th>
                    <th style="width: 24mm;">Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pengajuan->details as $detail)
                    @php $row = $items[$detail->id]; @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="pre">{{ $row['specification'] }}</td>
                        <td>{{ $row['asset_code'] }}</td>
                        <td class="center">{{ number_format((int) $row['quantity'], 0, ',', '.') }} {{ $detail->unit }}</td>
                        <td>{{ $row['condition'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="para">
            <strong>PIHAK KEDUA</strong> atas nama {{ $secondName }} menerima {{ $totalQty }} ({{ \App\Support\Terbilang::make($totalQty) }}) unit
            barang/peralatan sebagai penunjang pelaksanaan tugas-tugas di {{ $company }} dengan merk, kondisi, dan kode aset tersebut
            di atas dan <strong>PIHAK KEDUA</strong> wajib menggunakan, menjaga dan merawat dengan baik selama masa jabatan berlangsung
            serta akan mengembalikannya setelah masa penggunaan berakhir kepada <strong>PIHAK PERTAMA</strong>.
        </p>

        <p class="para">
            Demikian Berita Acara Serah Terima Barang Inventaris Perusahaan ini dibuat untuk dipergunakan sebagaimana mestinya.
        </p>

        <table class="signatures">
            <tr>
                <td class="place"></td>
                <td class="place"></td>
                <td class="place">{{ $bast['place'] }}, {{ $id($date, 'j F Y') }}</td>
            </tr>
            <tr>
                <td>Yang Menyerahkan,<br><strong>PIHAK PERTAMA</strong></td>
                <td>Mengetahui,</td>
                <td>Yang Menerima,<br><strong>PIHAK KEDUA</strong></td>
            </tr>
            <tr>
                <td class="space">
                    @if ($handedImage)
                        <img src="{{ $handedImage }}" alt="Tanda tangan pihak pertama">
                    @endif
                </td>
                <td class="space"></td>
                <td class="space"></td>
            </tr>
            <tr>
                <td><span class="name">{{ $bast['first_name'] }}</span><br>{{ $bast['first_position'] }}</td>
                <td>
                    @if ($bast['known_name'])
                        <span class="name">{{ $bast['known_name'] }}</span><br>{{ $bast['known_position'] }}
                    @endif
                </td>
                <td><span class="name">{{ $bast['second_name'] }}</span><br>{{ $bast['second_position'] }}</td>
            </tr>
        </table>

        <p class="footnote no-print">
            Tanda tangan basah dibubuhkan setelah dicetak. Tanda tangan digital pihak pertama dicetak otomatis bila tersedia.
        </p>
    </div>

    {{-- Halaman 2: Surat Komitmen Pemeliharaan Fasilitas Kantor --}}
    <div class="sheet letter {{ $letterhead ? 'has-kop' : '' }}">
        @if ($letterhead)
            <img class="kop" src="{{ $letterhead }}" alt="">
        @endif

        <table class="meta">
            <tr><td class="key">Tanggal</td><td>: {{ $id($date, 'j F Y') }}</td></tr>
            <tr><td class="key">Perihal</td><td>: <strong>Surat Komitmen Pemeliharaan Fasilitas Kantor</strong></td></tr>
        </table>

        <p class="para">Saya yang bertanda tangan di bawah ini:</p>

        <table class="meta">
            <tr><td class="key">Nama</td><td>: {{ $bast['second_name'] }}</td></tr>
            <tr><td class="key">NIK</td><td>: {{ $bast['second_nik'] }}</td></tr>
            <tr><td class="key">Alamat KTP</td><td>: <span class="line"></span></td></tr>
            <tr><td></td><td>&nbsp;&nbsp;<span class="line"></span></td></tr>
        </table>

        <p class="para">
            Dengan ini menyatakan bahwa saya berkomitmen untuk menjaga dan memelihara seluruh fasilitas kantor yang dipinjamkan
            kepada saya. Adapun ketentuan-ketentuan yang saya wajib patuhi adalah sebagai berikut:
        </p>

        <ol>
            <li>Menggunakan fasilitas kantor sesuai dengan peruntukannya dengan penuh tanggung jawab;</li>
            <li>Memastikan fasilitas kantor tetap dalam kondisi baik dan melaporkan segera jika terdapat kerusakan atau kehilangan;</li>
            <li>Tidak menyalahgunakan fasilitas kantor untuk kepentingan pribadi atau hal yang bertentangan dengan kebijakan perusahaan;</li>
            <li>Bertanggung jawab atas segala bentuk kerusakan atau kehilangan yang disebabkan oleh kelalaian pengguna fasilitas kantor baik yang disebabkan kesengajaan maupun tidak disengaja;</li>
            <li>Mengembalikan fasilitas kantor dalam kondisi semula, dalam keadaan baik, dan dapat didayagunakan apabila dialihkan kepada pengguna fasilitas kantor lain atau saya meninggalkan/mengundurkan diri dari perusahaan.</li>
        </ol>

        <p class="para">Apabila saya melanggar ketentuan di atas, saya bersedia menerima sanksi-sanksi berupa:</p>

        <ol type="a">
            <li>Teguran lisan atau tertulis, dan/atau</li>
            <li>Penggantian fasilitas yang rusak atau hilang sesuai dengan nilai yang ditetapkan oleh perusahaan melalui mekanisme penggantian langsung atau potong gaji/hak yang seharusnya diberikan.</li>
        </ol>

        <p class="para">
            Demikian surat komitmen ini saya buat dengan penuh kesadaran dan tanpa paksaan dari pihak manapun. Saya bersedia menerima
            segala konsekuensi sesuai dengan ketentuan Peraturan Perusahaan dan ketentuan hukum apabila melanggar komitmen ini.
        </p>

        <div class="sign">
            Hormat saya,
            <div class="space"></div>
            <span class="name" style="font-weight: bold; text-decoration: underline;">{{ $bast['second_name'] }}</span>
        </div>
    </div>
</body>
</html>
