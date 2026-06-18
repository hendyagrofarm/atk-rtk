<?php

namespace App\Exports;

use App\Models\StockIn;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class StockInReportExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, ShouldAutoSize, WithEvents
{
    protected Request $request;

    protected int $rowNumber = 0;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = StockIn::with(['entity', 'item.category', 'user'])
            ->latest();

        if ($this->request->filled('start_date')) {
            $query->whereDate('date', '>=', $this->request->start_date);
        }

        if ($this->request->filled('end_date')) {
            $query->whereDate('date', '<=', $this->request->end_date);
        }

        if ($this->request->filled('entity_id')) {
            $query->where('entity_id', $this->request->entity_id);
        }

        if ($this->request->filled('category_id')) {
            $query->whereHas('item', function ($itemQuery) {
                $itemQuery->where('category_id', $this->request->category_id);
            });
        }

        return $query->get();
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Entitas',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Satuan',
            'Jumlah Masuk',
            'Supplier',
            'User Input',
            'Keterangan',
        ];
    }

    public function map($stockIn): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $stockIn->date ? $stockIn->date->format('d/m/Y') : '-',
            $stockIn->entity->code ?? '-',
            $stockIn->item->code ?? '-',
            $stockIn->item->name ?? '-',
            $stockIn->item->category->name ?? '-',
            $stockIn->item->unit ?? '-',
            (int) $stockIn->quantity,
            $stockIn->supplier ?? '-',
            $stockIn->user->name ?? '-',
            $stockIn->note ?? '-',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:K1');
                $sheet->mergeCells('A2:K2');

                $sheet->setCellValue('A1', 'LAPORAN STOK MASUK');
                $sheet->setCellValue('A2', 'Tanggal Export: ' . now()->format('d-m-Y H:i'));

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);

                $headerRange = 'A4:K4';
                $tableRange = 'A4:K' . $highestRow;

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
                    $sheet->getStyle('B5:B' . $highestRow)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('C5:C' . $highestRow)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('G5:G' . $highestRow)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('H5:H' . $highestRow)->getAlignment()->setHorizontal('right');

                    $sheet->getStyle('H5:H' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle('H5:H' . $highestRow)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '047857'],
                        ],
                        'fill' => [
                            'fillType' => 'solid',
                            'startColor' => ['rgb' => 'D1FAE5'],
                        ],
                    ]);
                }

                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(14);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(28);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(16);
                $sheet->getColumnDimension('I')->setWidth(22);
                $sheet->getColumnDimension('J')->setWidth(20);
                $sheet->getColumnDimension('K')->setWidth(30);

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(8);
                $sheet->getRowDimension(4)->setRowHeight(22);
            },
        ];
    }
}