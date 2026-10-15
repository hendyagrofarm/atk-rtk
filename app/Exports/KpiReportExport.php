<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KpiReportExport implements WithMultipleSheets
{
    public function __construct(protected array $kpi)
    {
    }

    public function sheets(): array
    {
        $summary = $this->kpi['summary'];

        $rate = fn ($value) => $value === null ? '-' : $value . '%';

        return [
            new KpiSheetExport(
                'Ringkasan',
                ['Indikator', 'Nilai'],
                [
                    ['Total pengajuan dari stok', $summary['total_requests']],
                    ['Menunggu persetujuan', $summary['pending']],
                    ['Disetujui', $summary['approved']],
                    ['Ditolak', $summary['rejected']],
                    ['Unit diminta (pengajuan yang sudah diproses)', $summary['units_requested_processed']],
                    ['Unit disetujui', $summary['units_approved']],
                    ['Tingkat pemenuhan unit', $rate($summary['fulfillment_rate'])],
                    ['Pengajuan disetujui penuh', $rate($summary['full_approval_rate'])],
                    ['Rata-rata waktu proses (jam)', $summary['avg_processing_hours'] ?? '-'],
                    ['Baris barang dengan kekurangan stok', $summary['shortage_lines']],
                    ['Total unit kekurangan stok', $summary['shortage_units']],
                    ['Baris barang saat stok kosong', $summary['empty_lines']],
                    ['Baris barang dengan catatan stok', $summary['detail_with_snapshot'] . ' dari ' . $summary['detail_total']],
                    ['Catatan stok tersedia sejak', $summary['snapshot_since'] ?? '-'],
                ]
            ),
            new KpiSheetExport(
                'Per Barang',
                ['Kode', 'Barang', 'Kategori', 'Satuan', 'Jumlah Pengajuan', 'Unit Diminta', 'Unit Disetujui', 'Kali Kurang', 'Unit Kurang', 'Kali Stok Kosong'],
                array_map(fn ($row) => [
                    $row['code'], $row['name'], $row['category'], $row['unit'],
                    $row['request_lines'], $row['units_requested'], $row['units_approved'],
                    $row['shortage_lines'], $row['shortage_units'], $row['empty_lines'],
                ], $this->kpi['per_item'])
            ),
            new KpiSheetExport(
                'Per Lokasi',
                ['Entitas', 'Gudang', 'Pengajuan', 'Diproses', 'Ditolak', 'Unit Diminta', 'Unit Disetujui', 'Tingkat Pemenuhan', 'Unit Kurang'],
                array_map(fn ($row) => [
                    $row['entity'], $row['warehouse'], $row['requests'], $row['processed'], $row['rejected'],
                    $row['units_requested'], $row['units_approved'], $rate($row['fulfillment_rate']), $row['shortage_units'],
                ], $this->kpi['per_location'])
            ),
            new KpiSheetExport(
                'Alasan Penolakan',
                ['Alasan', 'Jumlah Pengajuan'],
                array_map(fn ($row) => [$row['label'], $row['total']], $this->kpi['rejection_reasons'])
            ),
        ];
    }
}
