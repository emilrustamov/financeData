<?php

namespace App\Exports;

use App\Models\Record;
use App\Models\Cash;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashExpensesSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $userName;

    public function __construct($startDate, $endDate, $userName)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userName = $userName;
    }

    public function collection()
    {
        return Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->selectRaw('cash_id, SUM(amount) as total')
            ->groupBy('cash_id')
            ->get()
            ->map(function ($record) {
                $record->cash_id = Cash::find($record->cash_id)->title ?? $record->cash_id;
                return $record;
            });
    }

    public function headings(): array
    {
        return [
            ['Экспорт выполнен пользователем: ' . $this->userName],
            ['Период: ' . $this->startDate . ' - ' . $this->endDate],
            [],
            [
                'Касса',
                'Сумма',
            ]
        ];
    }

    public function title(): string
    {
        return 'Расходы по кассам';
    }
}