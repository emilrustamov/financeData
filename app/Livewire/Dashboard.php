<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Record;
use App\Models\Cash;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;
use Asantibanez\LivewireCharts\Models\PieChartModel;
use App\Models\Objects;

class Dashboard extends Component
{
    public $selectedMonth;
    public $selectedYear;
    public $selectedCashes = [];
    public $allCashes = [];
    public $cashOneCategory;
    public $cashTwoCategory;
    public $cashOneCounter;
    public $cashTwoCounter;

    public function mount()
    {
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear = Carbon::now()->year;
        $this->allCashes = Cash::pluck('title', 'id')->toArray();
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
            ->whereYear('date', $this->selectedYear)
            ->whereMonth('date', $this->selectedMonth)
            ->whereIn('cash_id', $this->selectedCashes)
            ->selectRaw('cash_id, SUM(amount) as total')
            ->groupBy('cash_id')
            ->get();

        $columnChart = new ColumnChartModel();
        $displayData = [];
        $colorIndex = 0;
        foreach ($data as $item) {
            $cashTitle = $this->allCashes[$item->cash_id] ?? "Касса {$item->cash_id}";
            $color = $colors[$colorIndex % count($colors)];
            $columnChart->addColumn($cashTitle, $item->total, $color);
            $displayData[] = [
                'cash_id' => $cashTitle,
                'total'   => $item->total,
                'color'   => $color,
            ];
            $colorIndex++;
        }
        $columnChart->setColumnWidth(30);
        $categories = Objects::pluck('title', 'id')->toArray();
        $dataOne = Record::where('type', 0)
            ->whereYear('date', $this->selectedYear)
            ->whereMonth('date', $this->selectedMonth)
            ->where('cash_id', $this->cashOneCategory)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get();
        $pieChartOne = new PieChartModel();
        $pieDataOne = [];
        $i = 0;
        foreach ($dataOne as $item) {
            $category = \App\Models\ObjectCategories::find($item->category_id);
            $categoryTitle = $category ? $category->title : "Категория {$item->category_id}";
            $currentColor = $colors[$i % count($colors)];
            $pieChartOne->addSlice($categoryTitle, (float)$item->total, $currentColor);
            $pieDataOne[] = [
                'category' => $categoryTitle,
                'total'    => $item->total,
                'color'    => $currentColor,
            ];
            $i++;
        }

        $dataTwo = Record::where('type', 0)
            ->whereYear('date', $this->selectedYear)
            ->whereMonth('date', $this->selectedMonth)
            ->where('cash_id', $this->cashTwoCategory)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get();
        $pieChartTwo = new PieChartModel();
        $pieDataTwo = [];
        $i = 0;
        foreach ($dataTwo as $item) {
            $categoryTitle = $categories[$item->category_id] ?? "Категория {$item->category_id}";
            $currentColor = $colors[$i % count($colors)];
            $pieChartTwo->addSlice($categoryTitle, (float)$item->total, $currentColor);
            $pieDataTwo[] = [
                'category' => $categoryTitle,
                'total'    => $item->total,
                'color'    => $currentColor,
            ];
            $i++;
        }

        $dataThree = Record::where('type', 0)
            ->whereYear('date', $this->selectedYear)
            ->whereMonth('date', $this->selectedMonth)
            ->where('cash_id', $this->cashOneCounter)
            ->selectRaw('object_id, SUM(amount) as total')
            ->groupBy('object_id')
            ->get();

        $donutChartOne = new PieChartModel();
        $donutDataOne = [];
        $i = 0;
        foreach ($dataThree as $item) {
            $object = \App\Models\Objects::find($item->object_id);
            $objectTitle = $object ? $object->title : "Объект {$item->object_id}";
            $currentColor = $colors[$i % count($colors)];
            $donutChartOne->addSlice($objectTitle, (float)$item->total, $currentColor);
            $donutDataOne[] = [
                'object' => $objectTitle,
                'total'  => $item->total,
                'color'  => $currentColor,
            ];
            $i++;
        }
        $donutChartOne->asDonut();

        $dataFour = Record::where('type', 0)
            ->whereYear('date', $this->selectedYear)
            ->whereMonth('date', $this->selectedMonth)
            ->where('cash_id', $this->cashTwoCounter)
            ->selectRaw('object_id, SUM(amount) as total')
            ->groupBy('object_id')
            ->get();

        $donutChartTwo = new PieChartModel();
        $donutDataTwo = [];
        $i = 0;
        foreach ($dataFour as $item) {
            $object = \App\Models\Objects::find($item->object_id);
            $objectTitle = $object ? $object->title : "Объект {$item->object_id}";
            $currentColor = $colors[$i % count($colors)];
            $donutChartTwo->addSlice($objectTitle, (float)$item->total, $currentColor);
            $donutDataTwo[] = [
                'object' => $objectTitle,
                'total'  => $item->total,
                'color'  => $currentColor,
            ];
            $i++;
        }
        $donutChartTwo->asDonut();

        $dataProjects = Record::where('type', 0)
            ->whereYear('date', $this->selectedYear)
            ->whereMonth('date', $this->selectedMonth)
            ->selectRaw('project_id, SUM(amount) as total')
            ->groupBy('project_id')
            ->get();

        $projectChart = new ColumnChartModel();
        $i = 0;
        $projectData = [];
        foreach ($dataProjects as $item) {
            $project = \App\Models\Projects::find($item->project_id);
            $projectTitle = $project ? $project->title : "Проект {$item->project_id}";
            $color = $colors[$i % count($colors)];
            $projectChart->addColumn($projectTitle, $item->total, $color);
            $projectData[] = [
                'project' => $projectTitle,
                'total'   => $item->total,
                'color'   => $color,
            ];
            $i++;
        }
        $projectChart->setHorizontal(true);
        $projectChart->setColumnWidth(30);

        return view('livewire.dashboard', [
            'chart'           => $columnChart,
            'displayData'     => $displayData,
            'pieChartOne'     => $pieChartOne,
            'pieDataOne'      => $pieDataOne,
            'pieChartTwo'     => $pieChartTwo,
            'pieDataTwo'      => $pieDataTwo,
            'donutChartOne' => $donutChartOne,
            'donutDataOne'  => $donutDataOne,
            'donutChartTwo' => $donutChartTwo,
            'donutDataTwo'  => $donutDataTwo,
            'projectChart'    => $projectChart,
            'projectData'  => $projectData,
        ]);
    }
}
