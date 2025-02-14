<?php

namespace App\Exports;

use App\Models\Record;
use App\Models\Projects;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProjectExpensesSheet implements FromCollection, WithHeadings, WithTitle
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
            ->selectRaw('project_id, SUM(amount) as total')
            ->groupBy('project_id')
            ->get()
            ->map(function ($record) {
                $record->project_id = Projects::find($record->project_id)->title ?? $record->project_id;
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
                'Название проекта',
                'Общая сумма',
            ]
        ];
    }

    public function title(): string
    {
        return 'Расходы по проекту';
    }
}