<?php

namespace App\Exports;

use App\Models\Record;
use App\Models\Objects;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ObjectExpensesSheet implements FromCollection, WithHeadings, WithTitle, WithMapping, WithEvents
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
        $objects = Objects::whereJsonContains('users', auth()->user()->id)
            ->pluck('title', 'id')
            ->toArray();

        $recordsByObject = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->selectRaw('cash_id, object_id, SUM(amount) as total')
            ->groupBy('cash_id', 'object_id')
            ->get();

        $stackedObjectData = [];
        foreach ($recordsByObject as $record) {
            $stackedObjectData[$record->object_id][$record->cash_id] = $record->total;
        }

        $objectSummary = [];
        foreach ($objects as $objectId => $objectName) {
            $totals = [];
            foreach ($this->cashRegisters as $cash) {
                $total = isset($stackedObjectData[$objectId][$cash->id])
                    ? $stackedObjectData[$objectId][$cash->id]
                    : 0;
                $totals[$cash->title] = $total;
            }
            $objectSummary[] = [
                'object' => $objectName,
                'totals' => $totals,
            ];
        }

        $this->dataCount = count($objectSummary);
        return collect($objectSummary);
    }

    public function headings(): array
    {
        // Получаем уникальные валюты из коллекции касс
        $currencies = $this->cashRegisters->pluck('currency.symbol')->unique()->values()->toArray();

        $headings = [
            ['Экспорт выполнен пользователем: ' . $this->userName],
            ['Период: ' . $this->startDate . ' - ' . $this->endDate],
            [],
            // Первая колонка – Контрагент, вторая – Итог, далее колонки для каждой валюты
            array_merge(['Контрагент', 'Итог'], $currencies)
        ];

        return $headings;
    }

    public function map($row): array
    {
        // Собираем суммы по валютам: ключ – валюта, значение – сумма
        $currencyTotals = [];
        foreach ($this->cashRegisters as $cash) {
            $cashTitle = $cash->title;
            $currency = $cash->currency->symbol;
            $amount = isset($row['totals'][$cashTitle]) ? $row['totals'][$cashTitle] : 0;
            $currencyTotals[$currency] = ($currencyTotals[$currency] ?? 0) + $amount;
        }

        // Получаем уникальные валюты (также, как в headings)
        $currencies = $this->cashRegisters->pluck('currency.symbol')->unique()->values()->toArray();

        $mappedRow = [
            $row['object'],
            array_sum($row['totals']),
        ];

        // Для каждой валюты выводим сумму (или 0, если отсутствует)
        foreach ($currencies as $currency) {
            $mappedRow[] = isset($currencyTotals[$currency]) ? number_format((float)$currencyTotals[$currency], 2, '.', ' ') : 0;
        }

        return $mappedRow;
    }

    public function title(): string
    {
        return 'Расходы по контрагентам';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $startDataRow = 5;
                $endDataRow = $startDataRow + $this->dataCount - 1;
                // Количество столбцов: 2 (Контрагент, Итог) + число уникальных валют
                $currenciesCount = $this->cashRegisters->pluck('currency.symbol')->unique()->count();
                $colCount = 2 + $currenciesCount;
                $lastColumn = Coordinate::stringFromColumnIndex($colCount);

                $sheet->getStyle("A4:{$lastColumn}{$endDataRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                if ($this->dataCount > 0) {
                    $sumRow = $endDataRow + 1;
                    // Установим надпись "Итого" в колонке A
                    $sheet->setCellValue("A{$sumRow}", 'Итого');
                    // Для колонок с валютными значениями (начиная с C) добавляем формулы суммирования
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
