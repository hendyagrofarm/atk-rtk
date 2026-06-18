<?php

namespace App\Exports;

use App\Models\ItemStock;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class StockReportExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, ShouldAutoSize, WithEvents
{
    protected Request $request;

    protected int $rowNumber = 0;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = ItemStock::with(['entity', 'item.category'])
            ->whereHas('item')
            ->whereHas('entity')
            ->join('entities', 'item_stocks.entity_id', '=', 'entities.id')
            ->join('items', 'item_stocks.item_id', '=', 'items.id')
            ->select('item_stocks.*')
            ->orderBy('entities.code')
            ->orderBy('items.name');

        if ($this->request->filled('entity_id')) {
            $query->where('item_stocks.entity_id', $this->request->entity_id);
        }

        if ($this->request->filled('category_id')) {
            $query->whereHas('item', function ($itemQuery) {
                $itemQuery->where('category_id', $this->request->category_id);
            });
        }

        if ($this->request->filled('stock_status')) {
            if ($this->request->stock_status === 'low') {
                $query->whereColumn('item_stocks.current_stock', '<=', 'item_stocks.minimum_stock');
            }

            if ($this->request->stock_status === 'safe') {
                $query->whereColumn('item_stocks.current_stock', '>', 'item_stocks.minimum_stock');
            }
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
            'Entitas',
            'Kode Barang',
            'Nama Barang',
            'Jenis',
            'Kategori',
            'Satuan',
            'Stok Saat Ini',
            'Stok Minimum',
            'Status',
        ];
    }

    public function map($stock): array
    {
        $this->rowNumber++;

        $currentStock = (int) ($stock->current_stock ?? 0);
        $minimumStock = (int) ($stock->minimum_stock ?? 0);

        return [
            $this->rowNumber,
            $stock->entity->code ?? '-',
            $stock->item->code ?? '-',
            $stock->item->name ?? '-',
            $stock->item->type ?? '-',
            $stock->item->category->name ?? '-',
            $stock->item->unit ?? '-',
            $currentStock,
            $minimumStock,
            $currentStock <= $minimumStock ? 'Stok Menipis' : 'Aman',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();

                /*
                |--------------------------------------------------------------------------
                | Judul Laporan
                |--------------------------------------------------------------------------
                */
                $sheet->mergeCells('A1:J1');
                $sheet->mergeCells('A2:J2');

                $sheet->setCellValue('A1', 'LAPORAN STOK BARANG');
                $sheet->setCellValue('A2', 'Tanggal Export: ' . now()->format('d-m-Y H:i'));

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Header Tabel
                |--------------------------------------------------------------------------
                */
                $headerRange = 'A4:J4';
                $tableRange = 'A4:J' . $highestRow;

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '000000'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Border seluruh tabel
                |--------------------------------------------------------------------------
                */
                if ($highestRow >= 4) {
                    $sheet->getStyle($tableRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => 'thin',
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => 'center',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Filter dan freeze pane
                |--------------------------------------------------------------------------
                */
                $sheet->setAutoFilter($headerRange);
                $sheet->freezePane('A5');

                /*
                |--------------------------------------------------------------------------
                | Alignment isi tabel
                |--------------------------------------------------------------------------
                */
                if ($highestRow >= 5) {
                    $sheet->getStyle('A5:A' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('B5:B' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('E5:E' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('G5:G' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('H5:I' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('right');

                    $sheet->getStyle('J5:J' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('H5:I' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                }

                /*
                |--------------------------------------------------------------------------
                | Warna status
                |--------------------------------------------------------------------------
                */
                for ($row = 5; $row <= $highestRow; $row++) {
                    $status = $sheet->getCell('J' . $row)->getValue();

                    if ($status === 'Stok Menipis') {
                        $sheet->getStyle('J' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'B91C1C'],
                            ],
                            'fill' => [
                                'fillType' => 'solid',
                                'startColor' => ['rgb' => 'FEE2E2'],
                            ],
                        ]);
                    }

                    if ($status === 'Aman') {
                        $sheet->getStyle('J' . $row)->applyFromArray([
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
                }

                /*
                |--------------------------------------------------------------------------
                | Ukuran kolom
                |--------------------------------------------------------------------------
                */
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(28);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(16);
                $sheet->getColumnDimension('I')->setWidth(16);
                $sheet->getColumnDimension('J')->setWidth(18);

                /*
                |--------------------------------------------------------------------------
                | Tinggi baris
                |--------------------------------------------------------------------------
                */
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(8);
                $sheet->getRowDimension(4)->setRowHeight(22);
            },
        ];
    }
}