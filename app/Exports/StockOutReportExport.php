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
            ->whereIn('status', ['approved', 'disetujui'])
            ->when(
                ! $isSuperAdmin,
                fn ($q) => $q
                    ->whereIn('entity_id', $allowedEntityIds)
                    ->whereIn('warehouse_id', $allowedWarehouseIds)
            )
            ->when($this->request->filled('start_date'), fn ($q) => $q->whereDate('approved_at', '>=', $this->request->start_date))
            ->when($this->request->filled('end_date'), fn ($q) => $q->whereDate('approved_at', '<=', $this->request->end_date))
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
                    $requestItem->approved_at?->format('d/m/Y H:i') ?? '-',
                    $requestItem->user?->name ?? '-',
                    $detail->item?->code ?? '-',
                    $detail->item?->name ?? '-',
                    $detail->item?->category?->name ?? '-',
                    $detail->item?->unit ?? '-',
                    (int) ($detail->approved_quantity ?? 0),
                    $requestItem->approver?->name ?? '-',
                    $requestItem->approver_note ?? '-',
                ]);
            }
        }

        // Pengeluaran langsung oleh admin ditampilkan pada sheet yang sama.
        $directOuts = \App\Models\StockOut::query()
            ->visibleTo($user)
            ->filtered([
                'start_date' => $this->request->start_date,
                'end_date' => $this->request->end_date,
                'entity_id' => $entityId,
                'warehouse_id' => $warehouseId,
                'category_id' => $this->request->category_id,
                'issuer_id' => $this->request->user_id,
            ], $user)
            ->with(['entity', 'warehouse', 'issuer', 'items.item.category'])
            ->latest('issue_date')
            ->latest('id')
            ->get();

        foreach ($directOuts as $directOut) {
            foreach ($directOut->items as $line) {
                if ($this->request->filled('category_id')
                    && (int) $line->item?->category_id !== (int) $this->request->category_id) {
                    continue;
                }

                $rows->push([
                    ++$this->rowNumber,
                    $directOut->issue_number,
                    $directOut->entity?->code ?? '-',
                    $directOut->warehouse?->name ?? '-',
                    $directOut->warehouse?->code ?? '-',
                    $directOut->issue_date?->format('d/m/Y') ?? '-',
                    $directOut->recipient_name . ($directOut->recipient_department ? ' (' . $directOut->recipient_department . ')' : ''),
                    $line->item?->code ?? '-',
                    $line->item?->name ?? '-',
                    $line->item?->category?->name ?? '-',
                    $line->item?->unit ?? '-',
                    (int) $line->quantity,
                    ($directOut->issuer?->name ?? '-') . ' (langsung)',
                    $directOut->note ?: 'Pengeluaran langsung tanpa pengajuan',
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

                $sheet->mergeCells('A1:N1');
                $sheet->mergeCells('A2:N2');
                $sheet->setCellValue('A1', 'LAPORAN BARANG KELUAR PER ENTITAS DAN GUDANG');
                $sheet->setCellValue('A2', 'Tanggal Export: ' . now()->format('d-m-Y H:i'));

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => 'center'],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $headerRange = 'A4:N4';
                $tableRange = 'A4:N' . $highestRow;

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
                    $sheet->getStyle('L5:L' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle('L5:L' . $highestRow)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEE2E2']],
                    ]);
                }

                $widths = [
                    'A' => 6, 'B' => 22, 'C' => 12, 'D' => 24,
                    'E' => 16, 'F' => 20, 'G' => 22, 'H' => 18,
                    'I' => 28, 'J' => 18, 'K' => 12, 'L' => 18,
                    'M' => 22, 'N' => 30,
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
}
