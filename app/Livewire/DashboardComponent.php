<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Record;
use App\Models\ObjectCategories;
use App\Models\Projects;
use App\Models\Cash;
use App\Models\Objects;
use Asantibanez\LivewireCharts\Models\PieChartModel;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;

class DashboardComponent extends Component
{
    // --- Период 1 (основной) ---
    public $startDate;
    public $endDate;

    // --- Период 2 (сравнительный) ---
    public $compareStartDate;
    public $compareEndDate;

    // Фильтры
    public $selectedCategories = []; // чекбоксы
    public $filterProjectIds = [];   // toggle
    public $filterCashIds = [];      // toggle
    public $filterObjectIds = [];    // toggle

    // Справочники
    public $allCategories = [];
    public $allProjects = [];
    public $allCashes = [];
    public $allObjects = [];

    public function mount()
    {
        // Период 1: текущий месяц
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate   = Carbon::now()->endOfMonth()->toDateString();

        // Период 2: предыдущий месяц (пример)
        $this->compareStartDate = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $this->compareEndDate   = Carbon::now()->subMonth()->endOfMonth()->toDateString();

        // Заполняем справочники (id => title)
        $this->allCategories = ObjectCategories::pluck('title','id')->toArray();
        $this->allProjects   = Projects::pluck('title','id')->toArray();
        $this->allCashes     = Cash::pluck('title','id')->toArray();
        $this->allObjects    = Objects::pluck('title','id')->toArray();
    }

    /**
     * Записи основного периода (основные данные)
     */
    public function getFilteredRecordsProperty()
    {
        $query = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->with(['category','project','cash','object']);

        // Если выбраны категории
        if (!empty($this->selectedCategories)) {
            $query->whereIn('category_id', $this->selectedCategories);
        }
        // Фильтры по проектам, кассам, объектам
        if (!empty($this->filterProjectIds)) {
            $query->whereIn('project_id', $this->filterProjectIds);
        }
        if (!empty($this->filterCashIds)) {
            $query->whereIn('cash_id', $this->filterCashIds);
        }
        if (!empty($this->filterObjectIds)) {
            $query->whereIn('object_id', $this->filterObjectIds);
        }
        return $query->orderBy('date','desc')->get();
    }

    /**
     * Записи второго периода (сравнительный)
     */
    public function getCompareRecordsProperty()
    {
        $query = Record::where('type', 0)
            ->whereBetween('date', [$this->compareStartDate, $this->compareEndDate])
            ->with(['category','project','cash','object']);

        if (!empty($this->selectedCategories)) {
            $query->whereIn('category_id', $this->selectedCategories);
        }
        if (!empty($this->filterProjectIds)) {
            $query->whereIn('project_id', $this->filterProjectIds);
        }
        if (!empty($this->filterCashIds)) {
            $query->whereIn('cash_id', $this->filterCashIds);
        }
        if (!empty($this->filterObjectIds)) {
            $query->whereIn('object_id', $this->filterObjectIds);
        }
        return $query->orderBy('date','desc')->get();
    }

    // -- Toggle-методы --
    public function toggleCategoryFilter($catId)
    {
        if (in_array($catId, $this->selectedCategories)) {
            $this->selectedCategories = array_diff($this->selectedCategories, [$catId]);
        } else {
            $this->selectedCategories[] = $catId;
        }
    }

    public function toggleProjectFilter($projectId)
    {
        if (in_array($projectId, $this->filterProjectIds)) {
            $this->filterProjectIds = array_diff($this->filterProjectIds, [$projectId]);
        } else {
            $this->filterProjectIds[] = $projectId;
        }
    }

    public function toggleCashFilter($cashId)
    {
        if (in_array($cashId, $this->filterCashIds)) {
            $this->filterCashIds = array_diff($this->filterCashIds, [$cashId]);
        } else {
            $this->filterCashIds[] = $cashId;
        }
    }

    public function toggleObjectFilter($objectId)
    {
        if (in_array($objectId, $this->filterObjectIds)) {
            $this->filterObjectIds = array_diff($this->filterObjectIds, [$objectId]);
        } else {
            $this->filterObjectIds[] = $objectId;
        }
    }

    public function render()
    {
        // Получаем данные по основному и сравниваемому периоду
        $records = $this->filteredRecords;
        $totalAmount = $records->sum('amount');

        $compareRecords = $this->compareRecords;
        $compareTotalAmount = $compareRecords->sum('amount');

        // (A) Пир-диаграмма по категориям (Период 1)
        $grouped1 = $records->groupBy('category_id')->map->sum('amount');
        $pieChartModel = (new PieChartModel())
            ->setTitle('Расходы по категориям (Период 1)')
            ->setAnimated(true)
            ->legendPositionBottom();

        $colors = [
            '#f6ad55', '#fc8181', '#90cdf4', '#68d391',
            '#e53e3e', '#4299e1', '#ed8936', '#48bb78',
            '#9f7aea', '#38b2ac',
        ];
        $i = 0;
        foreach ($grouped1 as $catId => $sum) {
            $title = $this->allCategories[$catId] ?? 'Без категории';
            $pieChartModel->addSlice($title, floatval($sum), $colors[$i % count($colors)]);
            $i++;
        }

        // (B) Сравнительная диаграмма: Период 1 vs. Период 2 по категориям (multiColumn)
        $group2 = $compareRecords->groupBy('category_id')->map->sum('amount');
        $allCatIds = $grouped1->keys()->merge($group2->keys())->unique();

        $compareChart = (new ColumnChartModel())
            ->setTitle('Сравнение по категориям: Период 1 vs. Период 2')
            ->multiColumn()
            ->setAnimated(true)
            ->setColumnWidth(40);

        foreach ($allCatIds as $catId) {
            $val1 = floatval($grouped1[$catId] ?? 0);
            $val2 = floatval($group2[$catId] ?? 0);
            $catTitle = $this->allCategories[$catId] ?? 'Без категории';
            $compareChart->addSeriesColumn('Период 1', $catTitle, $val1);
            $compareChart->addSeriesColumn('Период 2', $catTitle, $val2);
        }

        // (C) Новый график и табличный вариант по кассам для выбранной категории
        $cashComparisonChart = null;
        $cashComparisonTableData = null;
        if (count($this->selectedCategories) === 1) {
            $catId = reset($this->selectedCategories);
            $catTitle = $this->allCategories[$catId] ?? 'Неизвестно';

            // Фильтруем записи основного периода по выбранной категории
            $recordsForCategory = $records->filter(function ($rec) use ($catId) {
                return $rec->category_id == $catId;
            });

            // Группировка по кассам
            $groupedByCash = $recordsForCategory->groupBy('cash_id')->map->sum('amount');
            $cashComparisonChart = (new ColumnChartModel())
                ->setTitle("Расход по кассам (категория: {$catTitle})")
                ->setAnimated(true)
                ->setColumnWidth(40);

            $j = 0;
            foreach ($groupedByCash as $cashId => $sumByCash) {
                $cashTitle = $this->allCashes[$cashId] ?? 'Неизвестно';
                $color = $colors[$j % count($colors)];
                $cashComparisonChart->addColumn($cashTitle, floatval($sumByCash), $color);
                $j++;
            }
            // Для табличного отображения данных по кассам
            $cashComparisonTableData = $groupedByCash;
        }

        return view('livewire.dashboard-component', [
            'records' => $records,
            'compareRecords' => $compareRecords,
            'totalAmount' => $totalAmount,
            'compareTotalAmount' => $compareTotalAmount,
            'pieChartModel' => $pieChartModel,
            'compareChart' => $compareChart,
            'cashComparisonChart' => $cashComparisonChart,
            'cashComparisonTableData' => $cashComparisonTableData,
        ]);
    }
}
