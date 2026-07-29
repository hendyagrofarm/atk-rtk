<?php

namespace App\Exports;

use App\Models\ItemStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class StockReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithCustomStartCell,
    ShouldAutoSize,
    WithEvents
{
    protected Request $request;

    protected int $rowNumber = 0;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Mengambil data stok sesuai hak akses user dan filter laporan.
     */
    public function collection()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $entityId = $this->request->filled('entity_id')
            && (
                $isSuperAdmin
                || $allowedEntityIds->contains(
                    (int) $this->request->entity_id
                )
            )
                ? (int) $this->request->entity_id
                : null;

        $warehouseId = $this->request->filled('warehouse_id')
            && (
                $isSuperAdmin
                || $allowedWarehouseIds->contains(
                    (int) $this->request->warehouse_id
                )
            )
                ? (int) $this->request->warehouse_id
                : null;

        $categoryId = $this->request->input('category_id');
        $stockStatus = $this->request->input('stock_status');
        $search = trim((string) $this->request->input('search'));

        return ItemStock::query()
            ->with([
                'entity',
                'warehouse',
                'item.category',
            ])
            ->whereHas('item')
            ->whereHas('entity')
            ->whereHas('warehouse')
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->whereIn('item_stocks.entity_id', $allowedEntityIds)
                    ->whereIn('item_stocks.warehouse_id', $allowedWarehouseIds)
            )
            ->when(
                $entityId,
                fn ($query) => $query
                    ->where('item_stocks.entity_id', $entityId)
            )
            ->when(
                $warehouseId,
                fn ($query) => $query
                    ->where('item_stocks.warehouse_id', $warehouseId)
            )
            ->when(
                filled($categoryId),
                fn ($query) => $query
                    ->whereHas(
                        'item',
                        fn ($itemQuery) => $itemQuery
                            ->where('category_id', $categoryId)
                    )
            )
            ->when(
                $search !== '',
                fn ($query) => $query
                    ->whereHas(
                        'item',
                        function ($itemQuery) use ($search) {
                            $itemQuery->where(function ($subQuery) use ($search) {
                                $subQuery
                                    ->where(
                                        'code',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'name',
                                        'like',
                                        '%' . $search . '%'
                                    );
                            });
                        }
                    )
            )
            ->when(
                $stockStatus === 'low',
                fn ($query) => $query
                    ->whereColumn(
                        'item_stocks.current_stock',
                        '<=',
                        'item_stocks.minimum_stock'
                    )
            )
            ->when(
                $stockStatus === 'safe',
                fn ($query) => $query
                    ->whereColumn(
                        'item_stocks.current_stock',
                        '>',
                        'item_stocks.minimum_stock'
                    )
            )
            ->join(
                'entities',
                'item_stocks.entity_id',
                '=',
                'entities.id'
            )
            ->join(
                'warehouses',
                'item_stocks.warehouse_id',
                '=',
                'warehouses.id'
            )
            ->join(
                'items',
                'item_stocks.item_id',
                '=',
                'items.id'
            )
            ->select('item_stocks.*')
            ->orderBy('entities.code')
            ->orderBy('warehouses.name')
            ->orderBy('items.name')
            ->get();
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
            'Gudang',
            'Kode Gudang',
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
            $stock->entity?->code ?? '-',
            $stock->warehouse?->name ?? '-',
            $stock->warehouse?->code ?? '-',
            $stock->item?->code ?? '-',
            $stock->item?->name ?? '-',
            $stock->item?->type ?? '-',
            $stock->item?->category?->name ?? '-',
            $stock->item?->unit ?? '-',
            $currentStock,
            $minimumStock,
            $currentStock <= $minimumStock
                ? 'Stok Menipis'
                : 'Aman',
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
                | Judul laporan
                |--------------------------------------------------------------------------
                */
                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');

                $sheet->setCellValue(
                    'A1',
                    'LAPORAN STOK BARANG PER ENTITAS DAN GUDANG'
                );

                $sheet->setCellValue(
                    'A2',
                    'Tanggal Export: ' . now()->format('d-m-Y H:i')
                );

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
                | Header tabel
                |--------------------------------------------------------------------------
                */
                $headerRange = 'A4:L4';
                $tableRange = 'A4:L' . $highestRow;

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
                | Alignment dan format angka
                |--------------------------------------------------------------------------
                */
                if ($highestRow >= 5) {
                    $sheet->getStyle('A5:A' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('B5:D' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('G5:G' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('I5:I' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('J5:K' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('right');

                    $sheet->getStyle('L5:L' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $sheet->getStyle('J5:K' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                }

                /*
                |--------------------------------------------------------------------------
                | Warna status
                |--------------------------------------------------------------------------
                */
                for ($row = 5; $row <= $highestRow; $row++) {
                    $status = $sheet
                        ->getCell('L' . $row)
                        ->getValue();

                    if ($status === 'Stok Menipis') {
                        $sheet->getStyle('L' . $row)->applyFromArray([
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
                        $sheet->getStyle('L' . $row)->applyFromArray([
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
                $sheet->getColumnDimension('C')->setWidth(24);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(28);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(20);
                $sheet->getColumnDimension('I')->setWidth(12);
                $sheet->getColumnDimension('J')->setWidth(16);
                $sheet->getColumnDimension('K')->setWidth(16);
                $sheet->getColumnDimension('L')->setWidth(18);

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