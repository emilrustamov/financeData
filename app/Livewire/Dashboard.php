<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Record;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DashboardExport;

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

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate   = Carbon::now()->endOfMonth()->toDateString();

        $user = auth()->user();
        $this->cashRegisters = $user->cashes;
        $this->allCashes = $this->cashRegisters->pluck('title', 'id')->toArray();
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
            $cashTitle = $this->allCashes[$item->cash_id] ?? "Касса {$item->cash_id}";
            $color = $colors[$colorIndex % count($colors)];
    
            $xCategories[] = $cashTitle;
    
            $cashChart->addColumn($cashTitle, $item->total, $color);
            $displayData[] = [
                'cash_id' => $cashTitle,
                'total'   => $item->total,
                'color'   => $color,
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

        $categories = \App\Models\ObjectCategories::pluck('title', 'id')->toArray();

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

        $catStackedChart = new ColumnChartModel();
        $catStackedChart->multiColumn()->stacked()->setColumnWidth(30);
        foreach ($catSummary as $catName => $cashTotals) {
            foreach ($cashTotals as $cashTitle => $total) {
                $catStackedChart->addSeriesColumn($cashTitle, $catName, $total);
            }
        }
        $catStackedChart->jsonConfig = [
            'xAxis' => [
                'categories' => array_keys($catSummary),
                'labels' => ['show' => 1]
            ],
        ];

        // Расходы по объектам
        $objectsList = \App\Models\Objects::pluck('title', 'id')->toArray();
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
        ->selectRaw('project_id, SUM(amount) as total')
        ->groupBy('project_id')
        ->get();
    
    $projectChart = new ColumnChartModel();
    $i = 0;
    $projectData = [];
    foreach ($dataProjects as $item) {
        $project = \App\Models\Projects::find($item->project_id);
        if ($project) {
            $projectTitle = $project->title;
            $color = $colors[$i % count($colors)];
            $projectChart->addColumn($projectTitle, $item->total, $color);
            $projectData[] = [
                'project' => $projectTitle,
                'total'   => $item->total,
                'color'   => $color,
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
            'objSummary'        => $objSummary,
        ]);
    }

    public function export()
    {
        $userName = auth()->user()->name;
        return Excel::download(new DashboardExport($this->startDate, $this->endDate, $this->cashRegisters, $userName), 'dashboard.xlsx');
    }
}
