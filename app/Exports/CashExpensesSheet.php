<?php

namespace App\Exports;

use App\Models\Record;
use App\Models\Cash;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class CashExpensesSheet implements FromCollection, WithHeadings, WithTitle, WithMapping, WithEvents
{
    protected $startDate;
    protected $endDate;
    protected $userName;
    protected $cashRegisters;
    protected $dataCount = 0;

    public function __construct($startDate, $endDate, $cashRegisters, $userName)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->cashRegisters = $cashRegisters; // Коллекция касс, доступных пользователю
        $this->userName  = $userName;
    }

    public function collection()
    {
        $allowedCashIds = $this->cashRegisters->pluck('id')->toArray();

        $records = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('cash_id', $allowedCashIds)
            ->selectRaw('cash_id, SUM(amount) as total')
            ->groupBy('cash_id')
            ->get()
            ->map(function ($record) {
                $cash = Cash::find($record->cash_id);
                $record->cash_title = $cash ? $cash->title : $record->cash_id;
                $record->currency = $cash ? $cash->currency->symbol : 'TMT';
                return $record;
            });

        $this->dataCount = count($records);
        return $records;
    }

    public function map($record): array
    {

        $currencies = $this->cashRegisters->pluck('currency.symbol')->unique()->values()->toArray();

        $row = [];
        $row[] = $record->cash_title;
        foreach ($currencies as $currency) {
            $row[] = ($record->currency === $currency) ? $record->total : '';
        }
        return $row;
    }

    public function headings(): array
    {
        $currencies = $this->cashRegisters->pluck('currency.symbol')->unique()->values()->toArray();

        return [
            ['Экспорт выполнен пользователем: ' . $this->userName],
            ['Период: ' . $this->startDate . ' - ' . $this->endDate],
            [],
            array_merge(['Касса'], $currencies)
        ];
    }
    public function title(): string
    {
        return 'Расходы по кассам';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Заголовки занимают 3 строки, шапка таблицы – 4-я строка, данные с 5-й строки:
                $startDataRow = 5;
                $endDataRow = $startDataRow + $this->dataCount - 1;
                // Число столбцов = количество валют + 1
                $currenciesCount = $this->cashRegisters->pluck('currency.symbol')->unique()->count();
                $totalColumns = 1 + $currenciesCount;
                $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);

                // Применяем рамки к ячейкам с шапкой и данными
                $sheet->getStyle("A4:{$lastColumn}{$endDataRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Добавляем строку с итоговой суммой для каждого столбца (начиная со 2-го)
                if ($this->dataCount > 0) {
                    $sumRow = $endDataRow + 1;
                    // Для первой колонки итог не нужен (это название)
                    $sheet->setCellValue("A{$sumRow}", 'Итого');
                    // Применяем формулу для каждой колонки начиная со столбца B
                    for ($col = 2; $col <= $totalColumns; $col++) {
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
