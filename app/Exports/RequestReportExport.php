<?php

namespace App\Exports;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        $entityId = $this->filterId($this->request->entity_id, $isSuperAdmin, $allowedEntityIds);
        $warehouseId = $this->filterId($this->request->warehouse_id, $isSuperAdmin, $allowedWarehouseIds);

        $query = RequestModel::query()
            ->with([
                'user',
                'entity',
                'warehouse',
                'details.item.category',
                'approver',
            ])
            ->when(
                ! $isSuperAdmin,
                fn ($q) => $q
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->when($this->request->filled('start_date'), fn ($q) => $q->whereDate('request_date', '>=', $this->request->start_date))
            ->when($this->request->filled('end_date'), fn ($q) => $q->whereDate('request_date', '<=', $this->request->end_date))
            ->when($this->request->filled('status'), fn ($q) => $q->where('status', $this->request->status))
            ->when($this->request->filled('user_id'), fn ($q) => $q->where('user_id', $this->request->user_id))
            ->when($entityId, fn ($q) => $q->where('entity_id', $entityId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when(
                $this->request->filled('category_id'),
                fn ($q) => $q->whereHas(
                    'details.item',
                    fn ($iq) => $iq->where('category_id', $this->request->category_id)
                )
            )
            ->latest();

        $rows = new Collection();

        foreach ($query->get() as $requestItem) {
            foreach ($requestItem->details as $detail) {
                $rows->push([
                    ++$this->rowNumber,
                    $requestItem->request_number,
                    $requestItem->entity?->code ?? '-',
                    $requestItem->warehouse?->name ?? '-',
                    $requestItem->warehouse?->code ?? '-',
                    $requestItem->request_date?->format('d/m/Y') ?? '-',
                    $requestItem->user?->name ?? '-',
                    $detail->item?->code ?? 'CUSTOM',
                    $detail->item?->name ?? $detail->custom_item_name ?? '-',
                    $detail->item?->category?->name ?? $detail->custom_category ?? '-',
                    $detail->item?->unit ?? $detail->unit ?? '-',
                    (int) $detail->quantity,
                    (int) ($detail->approved_quantity ?? 0),
                    $this->statusText($requestItem->status),
                    $requestItem->approver?->name ?? '-',
                    $requestItem->note ?? '-',
                    $requestItem->approver_note ?? '-',
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
            'Gudang',
            'Kode Gudang',
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:Q1');
                $sheet->mergeCells('A2:Q2');
                $sheet->setCellValue('A1', 'LAPORAN PENGAJUAN BARANG PER ENTITAS DAN GUDANG');
                $sheet->setCellValue('A2', 'Tanggal Export: ' . now()->format('d-m-Y H:i'));

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => 'center'],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $headerRange = 'A4:Q4';
                $tableRange = 'A4:Q' . $highestRow;

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
                    $sheet->getStyle('L5:M' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    for ($row = 5; $row <= $highestRow; $row++) {
                        $status = $sheet->getCell('N' . $row)->getValue();

                        $style = match ($status) {
                            'Disetujui' => [
                                'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
                                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D1FAE5']],
                            ],
                            'Ditolak' => [
                                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEE2E2']],
                            ],
                            default => [
                                'font' => ['bold' => true, 'color' => ['rgb' => 'B45309']],
                                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEF3C7']],
                            ],
                        };

                        $sheet->getStyle('N' . $row)->applyFromArray($style);
                    }
                }

                $widths = [
                    'A' => 6, 'B' => 22, 'C' => 12, 'D' => 24,
                    'E' => 16, 'F' => 18, 'G' => 22, 'H' => 18,
                    'I' => 28, 'J' => 18, 'K' => 12, 'L' => 18,
                    'M' => 18, 'N' => 16, 'O' => 22, 'P' => 30,
                    'Q' => 30,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
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

    private function statusText(?string $status): string
    {
        return match ($status) {
            'approved', 'disetujui' => 'Disetujui',
            'rejected', 'ditolak' => 'Ditolak',
            default => 'Pending',
        };
    }
}
