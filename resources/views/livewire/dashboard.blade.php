<!-- filepath: /d:/OSPanel/domains/financeData/resources/views/livewire/dashboard.blade.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
<body style="background-color: #eee;">
    <div class="container">
        <!-- Фильтр по месяцу и году остаётся без изменений -->
        <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;" class="bg-white p-3 rounded shadow-sm">
            <div class="mb-2">
                <label for="monthFilter" class="form-label">
                    <i class="fa fa-calendar-alt me-1"></i> Месяц
                </label>
                <select id="monthFilter" wire:model.live="selectedMonth" class="form-select rounded">
                    @foreach (range(1, 12) as $month)
                        <option value="{{ $month }}">
                            {{ \Carbon\Carbon::create()->month($month)->locale('ru')->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="yearFilter" class="form-label">
                    <i class="fa fa-calendar-day me-1"></i> Год
                </label>
                <select id="yearFilter" wire:model.live="selectedYear" class="form-select rounded">
                    @foreach (range(2020, Carbon\Carbon::now()->year) as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Расходы по кассам -->
        <div class="card mb-4 shadow-sm p-3" x-data="{ open: true }" x-transition>
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="font-bold text-lg">Расходы по кассам:</h3>
                <button type="button" class="btn btn-outline btn-sm" @click="open = !open">
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
            </div>
            <div x-show="open" x-transition>
                <div class="row my-3 align-items-center">
                    <!-- оставьте содержимое по кассам -->
                </div>
                <livewire:livewire-column-chart key="{{ $chart->reactiveKey() }}" :column-chart-model="$chart" />
                <div class="mt-4">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Касса</th>
                                <th>Сумма расходов</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($displayData as $data)
                                <tr>
                                    <td>
                                        <span
                                            style="display:inline-block;width:10px;height:10px;background-color:{{ $data['color'] }};margin-right:5px;"></span>
                                        {{ $data['cash_id'] }}
                                    </td>
                                    <td>{{ number_format($data['total'], 2, '.', ' ') }} TMT</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Расходы по категориям -->
        <div class="card mb-4 shadow-sm p-3" x-data="{ open: true }">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="font-bold text-lg">Расходы по категориям:</h3>
                <button type="button" class="btn btn-outline btn-sm" @click="open = !open">
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
            </div>
            <div x-show="open" x-transition>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <select wire:model.live="cashOneCategory" class="form-control mb-3">
                            @foreach ($allCashes as $id => $title)
                                <option value="{{ $id }}" @if ($cashTwoCategory == $id) disabled @endif>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                        <div style="height: 300px;">
                            @if (count($pieDataOne) > 0)
                                <livewire:livewire-pie-chart key="{{ $pieChartOne->reactiveKey() }}"
                                    :pie-chart-model="$pieChartOne" />
                            @else
                                <p class="text-center">Данных нет</p>
                            @endif
                        </div>
                        @if (count($pieDataOne) > 0)
                            <table class="table table-bordered table-hover mt-3">
                                <thead>
                                    <tr>
                                        <th>Категория</th>
                                        <th>Сумма расходов</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pieDataOne as $data)
                                        <tr>
                                            <td>
                                                <span
                                                    style="display:inline-block;width:10px;height:10px;background-color:{{ $data['color'] }};margin-right:5px;"></span>
                                                {{ $data['category'] }}
                                            </td>
                                            <td>{{ number_format($data['total'], 2, '.', ' ') }} TMT</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-center">Данных для таблицы нет</p>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <select wire:model.live="cashTwoCategory" class="form-control mb-3">
                            @foreach ($allCashes as $id => $title)
                                <option value="{{ $id }}" @if ($cashOneCategory == $id) disabled @endif>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                        <div style="height: 300px;">
                            @if (count($pieDataTwo) > 0)
                                <livewire:livewire-pie-chart key="{{ $pieChartTwo->reactiveKey() }}"
                                    :pie-chart-model="$pieChartTwo" />
                            @else
                                <p class="text-center">Данных нет</p>
                            @endif
                        </div>
                        @if (count($pieDataTwo) > 0)
                            <table class="table table-bordered table-hover mt-3">
                                <thead>
                                    <tr>
                                        <th>Категория</th>
                                        <th>Сумма расходов</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pieDataTwo as $data)
                                        <tr>
                                            <td>
                                                <span
                                                    style="display:inline-block;width:10px;height:10px;background-color:{{ $data['color'] }};margin-right:5px;"></span>
                                                {{ $data['category'] }}
                                            </td>
                                            <td>{{ number_format($data['total'], 2, '.', ' ') }} TMT</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-center">Данных для таблицы нет</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Расходы по контрагентам -->
        <div class="card mb-4 shadow-sm p-3" x-data="{ open: true }">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="font-bold text-lg">Расходы по контрагентам:</h3>
                <button type="button" class="btn btn-outline btn-sm" @click="open = !open">
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
            </div>
            <div x-show="open" x-transition>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <select wire:model.live="cashOneCounter" class="form-control mb-3">
                            @foreach ($allCashes as $id => $title)
                                <option value="{{ $id }}" @if ($cashTwoCounter == $id) disabled @endif>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                        <div style="height: 300px;">
                            @if ($donutDataOne && count($donutDataOne) > 0)
                                <livewire:livewire-pie-chart key="{{ $donutChartOne->reactiveKey() }}"
                                    :pie-chart-model="$donutChartOne" />
                            @else
                                <p class="text-center">Данных нет</p>
                            @endif
                        </div>
                        @if ($donutDataOne && count($donutDataOne) > 0)
                            <table class="table table-bordered table-hover mt-3">
                                <thead>
                                    <tr>
                                        <th>Контрагент</th>
                                        <th>Сумма расходов</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($donutDataOne as $data)
                                        <tr>
                                            <td>
                                                <span
                                                    style="display:inline-block;width:10px;height:10px;background-color:{{ $data['color'] }};margin-right:5px;"></span>
                                                {{ $data['object'] }}
                                            </td>
                                            <td>{{ number_format($data['total'], 2, '.', ' ') }} TMT</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-center">Данных для таблицы нет</p>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <select wire:model.live="cashTwoCounter" class="form-control mb-3">
                            @foreach ($allCashes as $id => $title)
                                <option value="{{ $id }}" @if ($cashOneCounter == $id) disabled @endif>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                        <div style="height: 300px;">
                            @if ($donutDataTwo && count($donutDataTwo) > 0)
                                <livewire:livewire-pie-chart key="{{ $donutChartTwo->reactiveKey() }}"
                                    :pie-chart-model="$donutChartTwo" />
                            @else
                                <p class="text-center">Данных нет</p>
                            @endif
                        </div>
                        @if ($donutDataTwo && count($donutDataTwo) > 0)
                            <table class="table table-bordered table-hover mt-3">
                                <thead>
                                    <tr>
                                        <th>Контрагент</th>
                                        <th>Сумма расходов</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($donutDataTwo as $data)
                                        <tr>
                                            <td>
                                                <span
                                                    style="display:inline-block;width:10px;height:10px;background-color:{{ $data['color'] }};margin-right:5px;"></span>
                                                {{ $data['object'] }}
                                            </td>
                                            <td>{{ number_format($data['total'], 2, '.', ' ') }} TMT</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-center">Данных для таблицы нет</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Расходы по проектам -->
        <div class="card mb-4 shadow-sm p-3" x-data="{ open: true }">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="font-bold text-lg">Расходы по проектам:</h3>
                <button type="button" class="btn btn-outline btn-sm" @click="open = !open">
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
            </div>
            <div x-show="open" x-transition>
                <div style="height: 300px;">
                    <livewire:livewire-column-chart key="{{ $projectChart->reactiveKey() }}" :column-chart-model="$projectChart" />
                </div>
                @if (count($projectData) > 0)
                    <table class="table table-bordered table-hover mt-3">
                        <thead>
                            <tr>
                                <th>Проект</th>
                                <th>Сумма расходов</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($projectData as $data)
                                <tr>
                                    <td>
                                        <span
                                            style="display:inline-block;width:10px;height:10px;background-color:{{ $data['color'] }};margin-right:5px;"></span>
                                        {{ $data['project'] }}
                                    </td>
                                    <td>{{ number_format($data['total'], 2, '.', ' ') }} TMT</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-center">Данных для таблицы нет</p>
                @endif
            </div>
        </div>
    </div>
</body>
