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

class StockOutReportExport implements FromCollection, WithHeadings, WithCustomStartCell, ShouldAutoSize, WithEvents
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
            ->whereIn('status', ['approved', 'disetujui'])
            ->latest();

        if ($this->request->filled('start_date')) {
            $query->whereDate('approved_at', '>=', $this->request->start_date);
        }

        if ($this->request->filled('end_date')) {
            $query->whereDate('approved_at', '<=', $this->request->end_date);
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
                    'Tanggal Keluar' => $requestItem->approved_at ? $requestItem->approved_at->format('d/m/Y H:i') : '-',
                    'Pemohon' => $requestItem->user->name ?? '-',
                    'Kode Barang' => $detail->item->code ?? '-',
                    'Nama Barang' => $detail->item->name ?? '-',
                    'Kategori' => $detail->item->category->name ?? '-',
                    'Satuan' => $detail->item->unit ?? '-',
                    'Jumlah Keluar' => (int) ($detail->approved_quantity ?? 0),
                    'Approver' => $requestItem->approver->name ?? '-',
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
            'Tanggal Keluar',
            'Pemohon',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Satuan',
            'Jumlah Keluar',
            'Approver',
            'Catatan Approver',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');

                $sheet->setCellValue('A1', 'LAPORAN BARANG KELUAR');
                $sheet->setCellValue('A2', 'Tanggal Export: ' . now()->format('d-m-Y H:i'));

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);

                $headerRange = 'A4:L4';
                $tableRange = 'A4:L' . $highestRow;

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
                    $sheet->getStyle('J5:J' . $highestRow)->getAlignment()->setHorizontal('right');

                    $sheet->getStyle('J5:J' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle('J5:J' . $highestRow)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEE2E2']],
                    ]);
                }

                $widths = [
                    'A' => 6, 'B' => 22, 'C' => 12, 'D' => 20,
                    'E' => 22, 'F' => 18, 'G' => 28, 'H' => 18,
                    'I' => 12, 'J' => 18, 'K' => 22, 'L' => 30,
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