<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Record;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DashboardExport;
use App\Models\Projects;
use App\Models\ObjectCategories;

class Dashboard extends Component
{
    public $startDate;
    public $endDate;
    public $selectedCashes = [];
    public $cashRegisters;
    public $allCashes = [];
    public $cashOneCategory;
    public $cashTwoCategory;
    public $cashOneCounter;
    public $cashTwoCounter;
    public $selectedCategory = '';
    public $selectedObj = '';
    public $selectedMonth = '';
    protected $listeners = [
        'onCategorySliceClick' => 'filterByCategoryFromChart',
    ];
    public function setCategory($category)
    {
        $this->selectedCategory = $category;
    }

    public function filterByCategoryFromChart($category)
    {
        $this->selectedCategory = $category;
    }
    

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate   = Carbon::now()->endOfMonth()->toDateString();
        $this->selectedCategory = '';
        $this->selectedObj = '';
        $user = auth()->user();
        $this->cashRegisters = $user->cashes;
        $this->allCashes = $this->cashRegisters->mapWithKeys(function ($cash) {
            return [$cash->id => $cash->title . " (" . $cash->currency->symbol . ")"];
        })->toArray();
        $this->selectedCashes = array_keys($this->allCashes);

        $this->cashOneCategory = array_key_first($this->allCashes);
        $this->cashTwoCategory = count($this->allCashes) > 1 ? array_keys($this->allCashes)[1] : $this->cashOneCategory;

        $this->cashOneCounter = $this->cashOneCategory;
        $this->cashTwoCounter = $this->cashTwoCategory;
    }

    public function render()
    {
        $colors = ['#f6ad55', '#fc8181', '#90cdf4', '#68d391', '#e53e3e', '#4299e1', '#ed8936', '#48bb78'];

        $data = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('cash_id', $this->selectedCashes)
            ->selectRaw('cash_id, SUM(amount) as total')
            ->groupBy('cash_id')
            ->get();

        $cashChart = new ColumnChartModel();
        $cashChart->setColumnWidth(30);

        $xCategories = [];
        $displayData = [];
        $colorIndex = 0;
        foreach ($data as $item) {
            $cash = $this->cashRegisters->firstWhere('id', $item->cash_id);
            $cashTitle = $cash->title . " (" . $cash->currency->symbol . ")";
            $color = $colors[$colorIndex % count($colors)];

            $xCategories[] = $cashTitle;

            $cashChart->addColumn($cashTitle, $item->total, $color);
            $displayData[] = [
                'cash_id'  => $cashTitle,
                'total'    => $item->total,
                'color'    => $color,
                'currency' => $cash->currency->symbol,  // добавлено
            ];
            $colorIndex++;
        }
        $xCategories = array_values(array_unique($xCategories));

        $config = [
            'chart' => [
                'type' => 'bar',
            ],
            'plotOptions' => [
                'bar' => [
                    'columnWidth' => '40%',
                    'distributed' => true,
                    'dataLabels' => [
                        'position' => 'top',
                    ],
                ],
            ],
            'grid' => [
                'padding' => [
                    'left'  => 20,
                    'right' => 20,
                ],
            ],
            'xAxis' => [
                'categories' => $xCategories,
                'labels' => [
                    'show' => true,
                ],
            ],
            'legend' => [
                'onItemClick' => [
                    'toggleDataSeries' => true,
                ],
            ],
        ];

        $cashChart->setJsonConfig($config);

        $categories = ObjectCategories::whereJsonContains('users', auth()->user()->id)
            ->pluck('title', 'id')->toArray();

        $recordsByCat = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('cash_id', $this->selectedCashes)
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

        // Подготовка опций для фильтра (все уникальные названия категорий)
        $catFilterOptions = array_keys($groupedCategories);

        $catSummary = [];
        foreach ($groupedCategories as $catName => $catIds) {
            foreach ($this->selectedCashes as $cashId) {
                $total = 0;
                foreach ($catIds as $id) {
                    $total += isset($stackedCatData[$id][$cashId]) ? $stackedCatData[$id][$cashId] : 0;
                }
                $cashTitle = $this->allCashes[$cashId] ?? $cashId;
                $catSummary[$catName][$cashTitle] = $total;
            }
        }

        // Фильтрация по выбранной категории, если установлено значение
        if (!empty($this->selectedCategory)) {
            if (isset($catSummary[$this->selectedCategory])) {
                $catSummary = [$this->selectedCategory => $catSummary[$this->selectedCategory]];
            } else {
                $catSummary = [];
            }
        }

        // Фильтруем категории, где суммарное значение равно 0 (для таблицы)
        foreach ($catSummary as $catName => $cashData) {
            if (array_sum($cashData) == 0) {
                unset($catSummary[$catName]);
            }
        }
        $globalTotalsCat = [];
        foreach ($catSummary as $catName => $cashData) {
            foreach ($cashData as $cashTitle => $value) {
                $globalTotalsCat[$cashTitle] = ($globalTotalsCat[$cashTitle] ?? 0) + (float)$value;
            }
        }
        $catHeader = array_keys(array_filter($globalTotalsCat, function ($value) {
            return (float)$value !== 0.0;
        }));

        foreach ($catSummary as $catName => $cashData) {
            $filteredCashData = [];
            foreach ($catHeader as $cashTitle) {
                $filteredCashData[$cashTitle] = $cashData[$cashTitle] ?? 0;
            }
            $catSummary[$catName] = $filteredCashData;
        }

        // Создание графика (без 0 значений)
        $catStackedChart = new ColumnChartModel();
        $catStackedChart->multiColumn()->stacked()->setColumnWidth(30);
        foreach ($catSummary as $catName => $cashTotals) {
            foreach ($cashTotals as $cashTitle => $total) {
                if ($total != 0) {
                    $catStackedChart->addSeriesColumn($cashTitle, $catName, $total);
                }
            }
        }
        $catStackedChart->jsonConfig = [
            'xAxis' => [
                'categories' => array_keys($catSummary),
                'labels' => ['show' => 1]
            ],

        ];
        // После формирования диаграммы по категориям, добавьте в конфиг:
        $catStackedChart->jsonConfig = array_merge($catStackedChart->jsonConfig, [
            'plotOptions' => [
                'series' => [
                    'cursor' => 'pointer',
                    'point' => [
                        'events' => [
                            'click' => 'function() {
                                var category = this.series.chart.xAxis[0].categories[this.x];
                                Livewire.dispatch("categorySelected", category);
                            }'
                        ]
                    ]
                ]
            ]
        ]);

        $detailedCatRecords = collect([]);
        $catMonthlyChart = null;
        if (!empty($this->selectedCategory)) {
            $catIds = $groupedCategories[$this->selectedCategory] ?? [];
            $detailedCatRecords = Record::where('type', 0)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('cash_id', $this->selectedCashes)
                ->whereIn('category_id', $catIds)
                ->get();
            // Удаляем фильтрацию по месяцу, т.к. показываем все транзакции
        }

        // Формирование массива доступных месяцев (без будущих)
        $availableMonths = [];
        if (!empty($this->selectedCategory)) {
            $monthsData = $detailedCatRecords->groupBy(function ($record) {
                return \Carbon\Carbon::parse($record->date)->format('Y-m');
            })->sortKeys();

            $currentMonth = \Carbon\Carbon::now()->format('Y-m');
            foreach ($monthsData as $month => $records) {
                if ($month <= $currentMonth) {
                    $availableMonths[$month] = \Carbon\Carbon::parse($month . '-01')
                        ->locale('ru')
                        ->translatedFormat('F Y');
                }
            }
            if (empty($this->selectedMonth) && count($availableMonths) > 0) {
                // По умолчанию выбираем последний доступный месяц
                $this->selectedMonth = array_key_last($availableMonths);
            }
            if (!empty($this->selectedMonth)) {
                $detailedCatRecords = $detailedCatRecords->filter(function ($record) {
                    return \Carbon\Carbon::parse($record->date)->format('Y-m') === $this->selectedMonth;
                });
            }
        }
        // Расходы по объектам
        $objectsList = \App\Models\Objects::whereJsonContains('users', auth()->user()->id)
            ->pluck('title', 'id')->toArray();
        $recordsByObj = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('cash_id', $this->selectedCashes)
            ->selectRaw('cash_id, object_id, SUM(amount) as total')
            ->groupBy('cash_id', 'object_id')
            ->get();

        $stackedObjData = [];
        foreach ($recordsByObj as $record) {
            $stackedObjData[$record->object_id][$record->cash_id] = $record->total;
        }

        $objSummary = [];
        foreach ($objectsList as $objId => $objTitle) {
            foreach ($this->selectedCashes as $cashId) {
                $total = isset($stackedObjData[$objId][$cashId]) ? $stackedObjData[$objId][$cashId] : 0;
                $cashTitle = $this->allCashes[$cashId] ?? $cashId;
                $objSummary[$objTitle][$cashTitle] = $total;
            }
        }
        $filteredObjSummary = [];
        foreach ($objSummary as $objTitle => $cashData) {
            $filteredObjSummary[$objTitle] = [];
            foreach ($cashData as $cashTitle => $value) {
                if ((float)$value !== 0.0) {
                    $filteredObjSummary[$objTitle][$cashTitle] = $value;
                }
            }
        }

        $globalTotals = [];
        foreach ($filteredObjSummary as $cashData) {
            foreach ($cashData as $cashTitle => $value) {
                $globalTotals[$cashTitle] = ($globalTotals[$cashTitle] ?? 0) + (float)$value;
            }
        }

        $objHeader = array_keys(array_filter($globalTotals, function ($value) {
            return $value !== 0.0;
        }));
        $objFilterOptions = array_values($objectsList);

        // Фильтруем данные, если выбран конкретный контрагент
        if (!empty($this->selectedObj)) {
            if (isset($objSummary[$this->selectedObj])) {
                $objSummary = [$this->selectedObj => $objSummary[$this->selectedObj]];
            } else {
                $objSummary = [];
            }
        }

        $objStackedChart = new ColumnChartModel();
        $objStackedChart->multiColumn()->stacked()->setColumnWidth(30);
        foreach ($objSummary as $objTitle => $cashTotals) {
            foreach ($cashTotals as $cashTitle => $total) {
                $objStackedChart->addSeriesColumn($cashTitle, $objTitle, $total);
            }
        }
        $objStackedChart->jsonConfig = [
            'xAxis' => [
                'categories' => array_keys($objSummary),
                'labels' => ['show' => 1]
            ],
        ];

        // Расходы по проектам

        $dataProjects = Record::where('type', 0)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->selectRaw('project_id, cash_id, SUM(amount) as total')
            ->groupBy('project_id', 'cash_id')
            ->get();

        $projectChart = new ColumnChartModel();
        $projectChart->setHorizontal(true);
        $projectChart->setColumnWidth(30);
        $i = 0;
        $projectData = [];

        // Сгруппируем данные по проекту и валюте
        $tempData = [];
        foreach ($dataProjects as $item) {
            $project = Projects::where('id', $item->project_id)
                ->whereJsonContains('users', auth()->user()->id)
                ->first();
            if ($project) {
                // Получаем кассу для записи, чтобы узнать валюту
                $cash = $this->cashRegisters->firstWhere('id', $item->cash_id);
                $currency = $cash ? $cash->currency->symbol : 'TMT';
                $projectId = $project->id;
                $projectTitle = $project->title;
                // Сохраним название проекта
                $tempData[$projectId]['title'] = $projectTitle;
                // Суммируем расходы по валюте
                if (isset($tempData[$projectId][$currency])) {
                    $tempData[$projectId][$currency] += $item->total;
                } else {
                    $tempData[$projectId][$currency] = $item->total;
                }
            }
        }

        // Формируем данные для диаграммы и таблицы
        foreach ($tempData as $projectId => $data) {
            $projectTitle = $data['title'];
            foreach ($data as $key => $total) {
                if ($key === 'title') continue;
                $label = $projectTitle . " (" . $key . ")";
                $color = $colors[$i % count($colors)];
                $projectChart->addColumn($label, $total, $color);
                $projectData[] = [
                    'project' => $label,
                    'total'   => $total,
                    'color'   => $color,
                    'currency' => $key,
                ];
                $i++;
            }
        }
        $projectChart->setHorizontal(true);
        $projectChart->setColumnWidth(30);

        return view('livewire.dashboard', [
            'chart'             => $cashChart,
            'displayData'       => $displayData,
            'catStackedChart'   => $catStackedChart,
            'objStackedChart'   => $objStackedChart,
            'projectChart'      => $projectChart,
            'projectData'       => $projectData,
            'catSummary'        => $catSummary,
            'objSummary'        => $filteredObjSummary,
            'objHeader'         => $objHeader,
            'catHeader'         => $catHeader,
            'catFilterOptions' => $catFilterOptions,
            'selectedCategory' => $this->selectedCategory,
            'objFilterOptions'  => $objFilterOptions,
            'selectedObj'       => $this->selectedObj,
            'detailedCatRecords'  => $detailedCatRecords,
            'catMonthlyChart'   => $catMonthlyChart,
            'availableMonths'   => $availableMonths,
            'selectedMonth'     => $this->selectedMonth,
        ]);
    }

    public function export()
    {
        $userName = auth()->user()->name;
        return Excel::download(new DashboardExport($this->startDate, $this->endDate, $this->cashRegisters, $userName), 'dashboard.xlsx');
    }
}
