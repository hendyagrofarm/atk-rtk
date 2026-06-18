<?php

namespace App\Exports;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class RequestReportExport implements FromCollection, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithEvents
{
    protected Request $request;

    protected int $rowNumber = 0;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = RequestModel::with(['user', 'entity', 'details.item.category', 'approver'])
            ->latest();

        if ($this->request->filled('start_date')) {
            $query->whereDate('request_date', '>=', $this->request->start_date);
        }

        if ($this->request->filled('end_date')) {
            $query->whereDate('request_date', '<=', $this->request->end_date);
        }

        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }

        if ($this->request->filled('user_id')) {
            $query->where('user_id', $this->request->user_id);
        }

        if ($this->request->filled('entity_id')) {
            $query->where('entity_id', $this->request->entity_id);
        }

        if ($this->request->filled('category_id')) {
            $query->whereHas('details.item', function ($itemQuery) {
                $itemQuery->where('category_id', $this->request->category_id);
            });
        }

        $rows = new Collection();

        foreach ($query->get() as $requestItem) {
            foreach ($requestItem->details as $detail) {
                $this->rowNumber++;

                $rows->push([
                    'No' => $this->rowNumber,
                    'Nomor Pengajuan' => $requestItem->request_number,
                    'Entitas' => $requestItem->entity->code ?? '-',
                    'Tanggal Pengajuan' => $requestItem->request_date ? $requestItem->request_date->format('d/m/Y') : '-',
                    'Pemohon' => $requestItem->user->name ?? '-',
                    'Kode Barang' => $detail->item->code ?? '-',
                    'Nama Barang' => $detail->item->name ?? '-',
                    'Kategori' => $detail->item->category->name ?? '-',
                    'Satuan' => $detail->item->unit ?? '-',
                    'Jumlah Diminta' => (int) $detail->quantity,
                    'Jumlah Disetujui' => (int) ($detail->approved_quantity ?? 0),
                    'Status' => $this->statusText($requestItem->status),
                    'Approver' => $requestItem->approver->name ?? '-',
                    'Catatan Staff' => $requestItem->note ?? '-',
                    'Catatan Approver' => $requestItem->approver_note ?? '-',
                ]);
            }
        }

        return $rows;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Pengajuan',
            'Entitas',
            'Tanggal Pengajuan',
            'Pemohon',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Satuan',
            'Jumlah Diminta',
            'Jumlah Disetujui',
            'Status',
            'Approver',
            'Catatan Staff',
            'Catatan Approver',
        ];
    }

    private function statusText(?string $status): string
    {
        return match ($status) {
            'approved', 'disetujui' => 'Disetujui',
            'rejected', 'ditolak' => 'Ditolak',
            default => 'Pending',
        };
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:O1');
                $sheet->mergeCells('A2:O2');

                $sheet->setCellValue('A1', 'LAPORAN PENGAJUAN BARANG');
                $sheet->setCellValue('A2', 'Tanggal Export: ' . now()->format('d-m-Y H:i'));

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);

                $headerRange = 'A4:O4';
                $tableRange = 'A4:O' . $highestRow;

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                if ($highestRow >= 4) {
                    $sheet->getStyle($tableRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => 'thin',
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                        'alignment' => ['vertical' => 'center'],
                    ]);
                }

                $sheet->setAutoFilter($headerRange);
                $sheet->freezePane('A5');

                if ($highestRow >= 5) {
                    $sheet->getStyle('A5:A' . $highestRow)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('C5:C' . $highestRow)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('D5:D' . $highestRow)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('I5:I' . $highestRow)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('J5:K' . $highestRow)->getAlignment()->setHorizontal('right');
                    $sheet->getStyle('L5:L' . $highestRow)->getAlignment()->setHorizontal('center');

                    $sheet->getStyle('J5:K' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    for ($row = 5; $row <= $highestRow; $row++) {
                        $status = $sheet->getCell('L' . $row)->getValue();

                        if ($status === 'Disetujui') {
                            $sheet->getStyle('L' . $row)->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
                                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D1FAE5']],
                            ]);
                        } elseif ($status === 'Ditolak') {
                            $sheet->getStyle('L' . $row)->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEE2E2']],
                            ]);
                        } else {
                            $sheet->getStyle('L' . $row)->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => 'B45309']],
                                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEF3C7']],
                            ]);
                        }
                    }
                }

                $widths = [
                    'A' => 6, 'B' => 22, 'C' => 12, 'D' => 18, 'E' => 22,
                    'F' => 18, 'G' => 28, 'H' => 18, 'I' => 12, 'J' => 18,
                    'K' => 18, 'L' => 16, 'M' => 22, 'N' => 30, 'O' => 30,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(8);
                $sheet->getRowDimension(4)->setRowHeight(22);
            },
        ];
    }
}