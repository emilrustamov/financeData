<?php

namespace App\Exports;

use App\Models\Record;
use App\Models\Objects;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class ObjectExpensesSheet implements FromCollection, WithHeadings, WithTitle, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $cashRegisters;
    protected $userName;

    public function __construct($startDate, $endDate, $cashRegisters, $userName)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->cashRegisters = $cashRegisters;
        $this->userName = $userName;
    }

    public function collection()
    {
        $objects = Objects::pluck('title', 'id')->toArray();

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
                $total = isset($stackedObjectData[$objectId][$cash->id]) ? $stackedObjectData[$objectId][$cash->id] : 0;
                $totals[$cash->title] = $total;
            }
            $objectSummary[] = [
                'object' => $objectName,
                'totals' => $totals,
            ];
        }

        return collect($objectSummary);
    }

    public function headings(): array
    {
        $headings = [
            ['Экспорт выполнен пользователем: ' . $this->userName],
            ['Период: ' . $this->startDate . ' - ' . $this->endDate],
            [],
            [
                'Контрагент',
                'Итог',
            ]
        ];

        foreach ($this->cashRegisters as $cash) {
            $headings[3][] = $cash->title;
        }

        return $headings;
    }

    public function map($row): array
    {
        $mappedRow = [
            $row['object'],
            array_sum($row['totals']),
        ];

        foreach ($this->cashRegisters as $cash) {
            $mappedRow[] = $row['totals'][$cash->title] ?? 0;
        }

        return $mappedRow;
    }

    public function title(): string
    {
        return 'Расходы по контрагентам';
    }
}