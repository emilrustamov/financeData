<?php

namespace App\Exports;

use App\Models\Record;
use App\Models\ObjectCategories;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class CategoryExpensesSheet implements FromCollection, WithHeadings, WithTitle, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $cashRegisters;

    public function __construct($startDate, $endDate, $cashRegisters)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->cashRegisters = $cashRegisters;
    }

    public function collection()
    {
        $categories = ObjectCategories::pluck('title', 'id')->toArray();

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

        return collect($catSummary);
    }

    public function headings(): array
    {
        $headings = [
            'Категория',
            'Итог',
        ];

        foreach ($this->cashRegisters as $cash) {
            $headings[] = $cash->title;
        }

        return $headings;
    }

    public function map($row): array
    {
        $mappedRow = [
            $row['category'],
            array_sum($row['totals']),
        ];
    
        foreach ($this->cashRegisters as $cash) {
            $mappedRow[] = $row['totals'][$cash->title] ?? 0;
        }
    
        return $mappedRow;
    }

    public function title(): string
    {
        return 'Расходы по категориям';
    }
}