<?php

namespace App\Exports;

use App\Models\Record;
use App\Models\Projects;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ProjectExpensesSheet implements FromCollection, WithHeadings, WithTitle, WithMapping, WithEvents
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
        $allowedProjects = Projects::whereJsonContains('users', auth()->user()->id)
            ->get()
            ->keyBy('id');
        $allowedProjectIds = $allowedProjects->keys()->toArray();

        $records = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('project_id', $allowedProjectIds)
            ->selectRaw('project_id, cash_id, SUM(amount) as total')
            ->groupBy('project_id', 'cash_id')
            ->get();

        $groupedData = [];
        foreach ($records as $record) {
            $projectId = $record->project_id;
            if (!isset($groupedData[$projectId])) {
                $groupedData[$projectId] = [
                    'project' => isset($allowedProjects[$projectId])
                        ? $allowedProjects[$projectId]->title
                        : $projectId,
                    'totals'  => [],
                ];
            }
            // Получаем валюту через кассу из cashRegisters
            $cash = $this->cashRegisters->firstWhere('id', $record->cash_id);
            $currency = $cash ? $cash->currency->symbol : 'TMT';

            if (!isset($groupedData[$projectId]['totals'][$currency])) {
                $groupedData[$projectId]['totals'][$currency] = 0;
            }
            $groupedData[$projectId]['totals'][$currency] += $record->total;
        }

        $this->dataCount = count($groupedData);
        return collect(array_values($groupedData));
    }

    public function map($row): array
    {
        $currencies = $this->cashRegisters->pluck('currency.symbol')
            ->unique()
            ->values()
            ->toArray();

        $overall = '';
        foreach ($row['totals'] as $currency => $amount) {
            $overall .= "{$currency}: " . number_format((float)$amount, 2, '.', ' ') . " ";
        }

        $mappedRow = [
            $row['project'],
            trim($overall),
        ];

        // Для каждой валюты выводим сумму по ней или 0, если отсутствует
        foreach ($currencies as $currency) {
            $mappedRow[] = isset($row['totals'][$currency])
                ? number_format((float)$row['totals'][$currency], 2, '.', ' ')
                : 0;
        }

        return $mappedRow;
    }

    public function headings(): array
    {
        $currencies = $this->cashRegisters->pluck('currency.symbol')
            ->unique()
            ->values()
            ->toArray();

        return [
            ['Экспорт выполнен пользователем: ' . $this->userName],
            ['Период: ' . $this->startDate . ' - ' . $this->endDate],
            [],
            array_merge(['Название проекта', 'Общая сумма'], $currencies)
        ];
    }

    public function title(): string
    {
        return 'Расходы по проекту';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $startDataRow = 5;
                $endDataRow = $startDataRow + $this->dataCount - 1;
                $currenciesCount = $this->cashRegisters->pluck('currency.symbol')->unique()->count();
                $colCount = 2 + $currenciesCount;
                $lastColumn = Coordinate::stringFromColumnIndex($colCount);

                $sheet->getStyle("A4:{$lastColumn}{$endDataRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                if ($this->dataCount > 0) {
                    $sumRow = $endDataRow + 1;
                    // Итог для колонки "Общая сумма" (B) устанавливать не будем – там текст со стыкованием валют
                    // Проставляем формулы для валютных столбцов начиная с 3-й
                    for ($col = 3; $col <= $colCount; $col++) {
                        $colLetter = Coordinate::stringFromColumnIndex($col);
                        $formula = "=SUM({$colLetter}{$startDataRow}:{$colLetter}{$endDataRow})";
                        $sheet->setCellValue("{$colLetter}{$sumRow}", $formula);
                    }
                    // Рамки и форматирование итоговой строки
                    $sheet->getStyle("A{$sumRow}:{$lastColumn}{$sumRow}")
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$sumRow}:{$lastColumn}{$sumRow}")
                        ->getFont()->setBold(true);
                }
            },
        ];
    }
}
