<?php

namespace App\Exports;

use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class StockTransferReportExport implements
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

    public function collection()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $query = StockTransfer::with([
            'entity',
            'sourceWarehouse',
            'destinationWarehouse',
            'item.category',
            'user',
        ]);

        if (! $isSuperAdmin) {
            $query->whereIn('entity_id', $allowedEntityIds)
                ->whereIn('source_warehouse_id', $allowedWarehouseIds)
                ->whereIn('destination_warehouse_id', $allowedWarehouseIds);
        }

        $query
            ->when($this->request->filled('start_date'),
                fn ($q) => $q->whereDate('transfer_date', '>=', $this->request->start_date))
            ->when($this->request->filled('end_date'),
                fn ($q) => $q->whereDate('transfer_date', '<=', $this->request->end_date))
            ->when($this->request->filled('entity_id'), function ($q) use ($isSuperAdmin, $allowedEntityIds) {
                $id = (int) $this->request->entity_id;
                if ($isSuperAdmin || $allowedEntityIds->contains($id)) {
                    $q->where('entity_id', $id);
                }
            })
            ->when($this->request->filled('source_warehouse_id'), function ($q) use ($isSuperAdmin, $allowedWarehouseIds) {
                $id = (int) $this->request->source_warehouse_id;
                if ($isSuperAdmin || $allowedWarehouseIds->contains($id)) {
                    $q->where('source_warehouse_id', $id);
                }
            })
            ->when($this->request->filled('destination_warehouse_id'), function ($q) use ($isSuperAdmin, $allowedWarehouseIds) {
                $id = (int) $this->request->destination_warehouse_id;
                if ($isSuperAdmin || $allowedWarehouseIds->contains($id)) {
                    $q->where('destination_warehouse_id', $id);
                }
            })
            ->when($this->request->filled('item_id'),
                fn ($q) => $q->where('item_id', $this->request->item_id))
            ->when($this->request->filled('user_id'),
                fn ($q) => $q->where('user_id', $this->request->user_id))
            ->when($this->request->filled('search'), function ($q) {
                $search = trim((string) $this->request->search);

                $q->where(function ($sub) use ($search) {
                    $sub->where('transfer_number', 'like', "%{$search}%")
                        ->orWhereHas('item', function ($itemQuery) use ($search) {
                            $itemQuery->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        })
                        ->orWhere('note', 'like', "%{$search}%");
                });
            });

        return $query->latest('transfer_date')->latest('id')->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Mutasi',
            'Tanggal Mutasi',
            'Entitas',
            'Gudang Asal',
            'Kode Gudang Asal',
            'Gudang Tujuan',
            'Kode Gudang Tujuan',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Satuan',
            'Jumlah Mutasi',
            'User Pencatat',
            'Keterangan',
        ];
    }

    public function map($transfer): array
    {
        return [
            ++$this->rowNumber,
            $transfer->transfer_number,
            $transfer->transfer_date?->format('d/m/Y') ?? '-',
            $transfer->entity?->code ?? '-',
            $transfer->sourceWarehouse?->name ?? '-',
            $transfer->sourceWarehouse?->code ?? '-',
            $transfer->destinationWarehouse?->name ?? '-',
            $transfer->destinationWarehouse?->code ?? '-',
            $transfer->item?->code ?? '-',
            $transfer->item?->name ?? '-',
            $transfer->item?->category?->name ?? '-',
            $transfer->item?->unit ?? '-',
            (int) $transfer->quantity,
            $transfer->user?->name ?? '-',
            $transfer->note ?: '-',
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:O1');
                $sheet->mergeCells('A2:O2');

                $sheet->setCellValue('A1', 'LAPORAN MUTASI STOK ANTAR GUDANG');
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
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F8FAFC']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                if ($highestRow >= 4) {
                    $sheet->getStyle($tableRange)->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                        'alignment' => ['vertical' => 'center'],
                    ]);
                }

                $sheet->setAutoFilter($headerRange);
                $sheet->freezePane('A5');

                if ($highestRow >= 5) {
                    $sheet->getStyle('M5:M' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle('M5:M' . $highestRow)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '1D4ED8']],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DBEAFE']],
                    ]);
                }

                $widths = [
                    'A' => 6, 'B' => 22, 'C' => 16, 'D' => 12,
                    'E' => 24, 'F' => 18, 'G' => 24, 'H' => 18,
                    'I' => 18, 'J' => 28, 'K' => 18, 'L' => 12,
                    'M' => 16, 'N' => 22, 'O' => 32,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
            },
        ];
    }
}
