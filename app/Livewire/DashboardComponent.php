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
use Illuminate\Support\HtmlString;

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

    protected $listeners = [
        'filterCategory' => 'filterCategory', // 👈 событие ⇒ метод
    ];

    public function mount()
    {
        // Период 1: текущий месяц
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate   = Carbon::now()->endOfMonth()->toDateString();

        // Период 2: предыдущий месяц (пример)
        $this->compareStartDate = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $this->compareEndDate   = Carbon::now()->subMonth()->endOfMonth()->toDateString();

        // Заполняем справочники (id => title)
        $this->allCategories = ObjectCategories::pluck('title', 'id')->toArray();
        $this->allProjects   = Projects::pluck('title', 'id')->toArray();
        $this->allCashes     = Cash::pluck('title', 'id')->toArray();
        $this->allObjects    = Objects::pluck('title', 'id')->toArray();
    }

    public function filterCategory(array $slice): void
    {
        $catId = $slice['extras']['cat_id'] ?? null;

        if ($catId) {
            // категория уже единственная? -> снимаем фильтр
            $this->selectedCategories =
                (count($this->selectedCategories) === 1 && $this->selectedCategories[0] == $catId)
                ? []          // показать всё
                : [$catId];   // показать выбранную
        }
    }


    /**
     * Записи основного периода (основные данные)
     */
    public function getFilteredRecordsProperty()
    {
        $query = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->with(['category', 'project', 'cash', 'object']);

        /* ---------- категории ---------- */
        if ($this->selectedCategories) {
            $ids        = array_filter($this->selectedCategories, fn($id) => !is_null($id));
            $hasNullCat = in_array(null, $this->selectedCategories, true);

            $query->where(function ($q) use ($ids, $hasNullCat) {
                if ($ids) {
                    $q->whereIn('category_id', $ids);
                }
                if ($hasNullCat) {
                    $ids ? $q->orWhereNull('category_id')
                        : $q->whereNull('category_id');
                }
            });
        }

        /* ---------- остальные фильтры ---------- */
        if ($this->filterProjectIds) {
            $query->whereIn('project_id', $this->filterProjectIds);
        }
        if ($this->filterCashIds) {
            $query->whereIn('cash_id',    $this->filterCashIds);
        }
        if ($this->filterObjectIds) {
            $query->whereIn('object_id',  $this->filterObjectIds);
        }

        return $query->orderBy('date', 'desc')->get();
    }


    public function updatedStartDate()
    {
        $this->selectedCategories = [];
    }

    public function updatedEndDate()
    {
        $this->selectedCategories = [];
    }

    /**
     * Записи второго периода (сравнительный)
     */
    public function getCompareRecordsProperty()
    {
        $query = Record::where('type', 0)
            ->whereBetween('date', [$this->compareStartDate, $this->compareEndDate])
            ->with(['category', 'project', 'cash', 'object']);

        /* ---------- категории ---------- */
        if ($this->selectedCategories) {
            $ids        = array_filter($this->selectedCategories, fn($id) => !is_null($id));
            $hasNullCat = in_array(null, $this->selectedCategories, true);

            $query->where(function ($q) use ($ids, $hasNullCat) {
                if ($ids) {
                    $q->whereIn('category_id', $ids);
                }
                if ($hasNullCat) {
                    $ids ? $q->orWhereNull('category_id')
                        : $q->whereNull('category_id');
                }
            });
        }

        /* ---------- остальные фильтры ---------- */
        if ($this->filterProjectIds) {
            $query->whereIn('project_id', $this->filterProjectIds);
        }
        if ($this->filterCashIds) {
            $query->whereIn('cash_id',    $this->filterCashIds);
        }
        if ($this->filterObjectIds) {
            $query->whereIn('object_id',  $this->filterObjectIds);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    // -- Toggle-методы --
    // Livewire-класс
    public function toggleCategoryFilter($catId = null): void
    {
        // если пришла пустая строка — приводим к null
        if ($catId === '') {
            $catId = null;
        }
    
        if (in_array($catId, $this->selectedCategories, true)) {
            $this->selectedCategories = array_values(
                array_diff($this->selectedCategories, [$catId])
            );
        } else {
            $this->selectedCategories[] = $catId;   // null допустим
        }
    }

    private function applyCategoryFilter($query): void
{
    if (!$this->selectedCategories) {
        return;
    }

    /* пустую строку также трактуем как null */
    $ids        = array_filter(
        $this->selectedCategories,
        fn ($id) => $id !== '' && !is_null($id)
    );
    $hasNullCat = in_array(null, $this->selectedCategories, true) ||
                  in_array('',   $this->selectedCategories, true);

    $query->where(function ($q) use ($ids, $hasNullCat) {
        if ($ids) {              // конкретные категории
            $q->whereIn('category_id', $ids);
        }
        if ($hasNullCat) {       // «Без категории»
            $ids ? $q->orWhereNull('category_id')
                 : $q->whereNull('category_id');
        }
    });
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
            ->legendPositionBottom()
            ->withOnSliceClickEvent('filterCategory')
            ->withDataLabels()
            ->setJsonConfig([
                'dataLabels.formatter' => "function(val, opts) {
                var p = opts.w.globals.seriesPercent[opts.seriesIndex][0];
                return p < 3 ? '' : p.toFixed(1) + '%'; // Убрали значение, оставили только процент
            }",
            ], JSON_UNESCAPED_SLASHES | JSON_HEX_APOS);



        $colors = [
            '#f6ad55',
            '#fc8181',
            '#90cdf4',
            '#68d391',
            '#e53e3e',
            '#4299e1',
            '#ed8936',
            '#48bb78',
            '#9f7aea',
            '#38b2ac',
        ];
        $i = 0;
        foreach ($grouped1 as $catId => $sum) {
            $title = $this->allCategories[$catId] ?? 'Без категории';

            // extras[] попадёт в $slice['extras']
            $pieChartModel->addSlice(
                $title,
                (float) $sum,
                $colors[$i % count($colors)],
                ['cat_id' => $catId]                 // 👈 передаём ID
            );
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

        $categorySummary = $grouped1->map(function ($sum, $catId) use ($records) {
            return [
                'cat_id'     => $catId,
                'title'      => $this->allCategories[$catId] ?? 'Без категории',
                'trx_count'  => $records->where('category_id', $catId)->count(),
                'amount'     => $sum,
            ];
        })->sortByDesc('amount');   // по убыванию суммы

        $compareCategorySummary = $group2->map(function ($sum, $catId) use ($compareRecords) {
            return [
                'cat_id'     => $catId,
                'title'      => $this->allCategories[$catId] ?? 'Без категории',
                'trx_count'  => $compareRecords->where('category_id', $catId)->count(),
                'amount'     => $sum,
            ];
        })->sortByDesc('amount');

        return view('livewire.dashboard-component', [
            'records' => $records,
            'compareRecords' => $compareRecords,
            'totalAmount' => $totalAmount,
            'compareTotalAmount' => $compareTotalAmount,
            'pieChartModel' => $pieChartModel,
            'compareChart' => $compareChart,
            'cashComparisonChart' => $cashComparisonChart,
            'cashComparisonTableData' => $cashComparisonTableData,
            'categorySummary' => $categorySummary,
            'compareCategorySummary' => $compareCategorySummary,
        ]);
    }
}
