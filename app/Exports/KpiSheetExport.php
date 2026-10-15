<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KpiSheetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected string $title,
        protected array $headings,
        protected array $rows
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F8FAFC']],
            ],
        ];
    }
}
