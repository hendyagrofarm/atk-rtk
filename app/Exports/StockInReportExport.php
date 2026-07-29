<?php

namespace App\Exports;

use App\Models\StockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $entityId = $this->filterId($this->request->entity_id, $isSuperAdmin, $allowedEntityIds);
        $warehouseId = $this->filterId($this->request->warehouse_id, $isSuperAdmin, $allowedWarehouseIds);

        return StockIn::query()
            ->with(['entity', 'warehouse', 'item.category', 'user'])
            ->when(
                ! $isSuperAdmin,
                fn ($q) => $q
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->when($this->request->filled('start_date'), fn ($q) => $q->whereDate('date', '>=', $this->request->start_date))
            ->when($this->request->filled('end_date'), fn ($q) => $q->whereDate('date', '<=', $this->request->end_date))
            ->when($entityId, fn ($q) => $q->where('entity_id', $entityId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when(
                $this->request->filled('category_id'),
                fn ($q) => $q->whereHas(
                    'item',
                    fn ($iq) => $iq->where('category_id', $this->request->category_id)
                )
            )
            ->latest()
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
            'Tanggal',
            'Entitas',
            'Gudang',
            'Kode Gudang',
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
        return [
            ++$this->rowNumber,
            $stockIn->date?->format('d/m/Y') ?? '-',
            $stockIn->entity?->code ?? '-',
            $stockIn->warehouse?->name ?? '-',
            $stockIn->warehouse?->code ?? '-',
            $stockIn->item?->code ?? '-',
            $stockIn->item?->name ?? '-',
            $stockIn->item?->category?->name ?? '-',
            $stockIn->item?->unit ?? '-',
            (int) $stockIn->quantity,
            $stockIn->supplier ?? '-',
            $stockIn->user?->name ?? '-',
            $stockIn->note ?? '-',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->formatSheet(
                    $event,
                    'LAPORAN STOK MASUK PER ENTITAS DAN GUDANG',
                    'M',
                    'J',
                    [
                        'A' => 6, 'B' => 14, 'C' => 12, 'D' => 24,
                        'E' => 16, 'F' => 18, 'G' => 28, 'H' => 18,
                        'I' => 12, 'J' => 16, 'K' => 22, 'L' => 20,
                        'M' => 30,
                    ]
                );
            },
        ];
    }

    private function filterId($value, bool $isSuperAdmin, $allowedIds): ?int
    {
        if (! filled($value)) {
            return null;
        }

        $id = (int) $value;

        return $isSuperAdmin || $allowedIds->contains($id) ? $id : null;
    }

    private function formatSheet(AfterSheet $event, string $title, string $lastColumn, string $numberColumn, array $widths): void
    {
        $sheet = $event->sheet->getDelegate();
        $highestRow = $sheet->getHighestRow();

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', 'Tanggal Export: ' . now()->format('d-m-Y H:i'));

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 11],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        $headerRange = "A4:{$lastColumn}4";
        $tableRange = "A4:{$lastColumn}{$highestRow}";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F8FAFC']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
        ]);

        $sheet->getStyle($tableRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
            'alignment' => ['vertical' => 'center'],
        ]);

        $sheet->setAutoFilter($headerRange);
        $sheet->freezePane('A5');

        if ($highestRow >= 5) {
            $sheet->getStyle("{$numberColumn}5:{$numberColumn}{$highestRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');

            $sheet->getStyle("{$numberColumn}5:{$numberColumn}{$highestRow}")
                ->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D1FAE5']],
                ]);
        }

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(8);
        $sheet->getRowDimension(4)->setRowHeight(22);
    }
}
