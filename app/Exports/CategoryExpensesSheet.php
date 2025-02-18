<?php

namespace App\Exports;

use App\Models\Record;
use App\Models\ObjectCategories;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class CategoryExpensesSheet implements FromCollection, WithHeadings, WithTitle, WithMapping, WithEvents
{
    protected $startDate;
    protected $endDate;
    protected $cashRegisters;
    protected $userName;
    protected $dataCount = 0;


    public function __construct($startDate, $endDate, $cashRegisters, $userName)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->cashRegisters = $cashRegisters;
        $this->userName  = $userName;
    }

    public function collection()
    {
        $categories = ObjectCategories::whereJsonContains('users', auth()->user()->id)
            ->pluck('title', 'id')
            ->toArray();

        $recordsByCat = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->selectRaw('cash_id, category_id, SUM(amount) as total')
            ->groupBy('cash_id', 'category_id')
            ->get();

        $stackedCatData = [];
        foreach ($recordsByCat as $record) {
            $stackedCatData[$record->category_id][$record->cash_id] = $record->total;
        }

        $groupedCategories = [];
        foreach ($categories as $catId => $catName) {
            $groupedCategories[$catName][] = $catId;
        }

        $catSummary = [];
        foreach ($groupedCategories as $catName => $catIds) {
            $totals = [];
            foreach ($this->cashRegisters as $cash) {
                $total = 0;
                foreach ($catIds as $id) {
                    $total += isset($stackedCatData[$id][$cash->id]) ? $stackedCatData[$id][$cash->id] : 0;
                }
                $totals[$cash->title] = $total;
            }
            $catSummary[] = [
                'category' => $catName,
                'totals' => $totals,
            ];
        }
        $this->dataCount = count($catSummary);
        return collect($catSummary);
    }

    public function headings(): array
    {
        $headings = [
            ['Экспорт выполнен пользователем: ' . $this->userName],
            ['Период: ' . $this->startDate . ' - ' . $this->endDate],
            [],
            array_merge(
                ['Категория', 'Итог'],
                array_map(fn($cash) => $cash->title . ' (' . $cash->currency->symbol . ')', $this->cashRegisters->all())
            )
        ];

        return $headings;
    }

    public function map($row): array
    {
        // Группировка расходов по валюте для итогового столбца
        $currencyTotals = [];
        foreach ($this->cashRegisters as $cash) {
            $cashTitle = $cash->title;
            $currency = $cash->currency->symbol;
            $amount = isset($row['totals'][$cashTitle]) ? $row['totals'][$cashTitle] : 0;
            $currencyTotals[$currency] = ($currencyTotals[$currency] ?? 0) + $amount;
        }

        $totalStr = '';
        foreach ($currencyTotals as $currency => $total) {
            $totalStr .= $currency . ': ' . number_format((float)$total, 2, '.', ' ') . ' ';
        }

        $mappedRow = [
            $row['category'],
            trim($totalStr),
        ];

        // Для каждой кассы выводим индивидуальные расходы
        foreach ($this->cashRegisters as $cash) {
            $mappedRow[] = isset($row['totals'][$cash->title])
                ? number_format((float)$row['totals'][$cash->title], 2, '.', ' ')
                : 0;
        }

        return $mappedRow;
    }

    public function title(): string
    {
        return 'Расходы по категориям';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $startDataRow = 5;
                $endDataRow = $startDataRow + $this->dataCount - 1;
                // Количество столбцов = 2 (Категория, Итог) + количество касс
                $colCount = 2 + count($this->cashRegisters);
                $lastColumn = Coordinate::stringFromColumnIndex($colCount);

                $sheet->getStyle("A4:{$lastColumn}{$endDataRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                if ($this->dataCount > 0) {
                    $sumRow = $endDataRow + 1;
                    // Пропускаем столбец B (Итог) так как там текст
                    for ($col = 3; $col <= $colCount; $col++) {
                        $colLetter = Coordinate::stringFromColumnIndex($col);
                        $formula = "=SUM({$colLetter}{$startDataRow}:{$colLetter}{$endDataRow})";
                        $sheet->setCellValue("{$colLetter}{$sumRow}", $formula);
                    }
                    $sheet->getStyle("A{$sumRow}:{$lastColumn}{$sumRow}")
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$sumRow}:{$lastColumn}{$sumRow}")
                        ->getFont()->setBold(true);
                }
            },
        ];
    }
}
