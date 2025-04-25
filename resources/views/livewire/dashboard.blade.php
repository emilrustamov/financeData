<div class="container pdf-container">
    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;" class="bg-white p-3 rounded shadow-sm">
        <div class="mb-2">
            <label for="startDate" class="form-label">
                <i class="fa fa-calendar-alt me-1"></i> Начало периода
            </label>
            <input type="date" id="startDate" wire:model.change="startDate" class="form-select rounded">
        </div>
        <div>
            <label for="endDate" class="form-label">
                <i class="fa fa-calendar-day me-1"></i> Конец периода
            </label>
            <input type="date" id="endDate" wire:model.change="endDate" class="form-select rounded">
        </div>
    </div>

    <div class="mb-4 d-flex justify-content-between">
        <button id="downloadPdf" class="btn btn-primary">
            <i class="fas fa-file-pdf me-1"></i> Скачать PDF
        </button>
        <button wire:click="export" class="btn btn-primary">
            <i class="fas fa-file-excel me-1"></i> Скачать Excel
        </button>
    </div>
    <div class="card mb-4 shadow-sm p-3 " x-data="{ open: true }" x-transition>
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="font-bold text-lg">Расходы по кассам:</h3>
            <button type="button" class="btn btn-outline btn-sm" @click="open = !open">
                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>
        <div x-show="open" x-transition>
            <div class="row my-3 align-items-center">

            </div>
            <livewire:livewire-column-chart key="{{ $chart->reactiveKey() }}" :column-chart-model="$chart" />
            <div class="mt-4">
                <table class="table table-bordered table-hover ">
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
                                <td>{{ number_format($data['total'], 2, '.', ' ') }} {{ $data['currency'] }}</td>
                            </tr>
                        @endforeach
                        @php
                            $totals = [];
                            foreach ($displayData as $d) {
                                $totals[$d['currency']] = ($totals[$d['currency']] ?? 0) + $d['total'];
                            }
                        @endphp
                        <tr class="fw-bold">
                            <td>Итого</td>
                            <td>
                                @foreach ($totals as $currency => $sum)
                                    {{ $currency }}: {{ number_format($sum, 2, '.', ' ') }}
                                    @if (!$loop->last)
                                        <br>
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                        </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm p-3" x-data="{ open: true }">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="font-bold text-lg">Расходы по категориям:</h3>
            <button type="button" class="btn btn-outline btn-sm" @click="open = !open">
                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>
    
        @if ($selectedCategory == '')
            <div x-show="open" x-transition>
                <div style="height: 300px;">
                    <livewire:livewire-column-chart key="{{ $catStackedChart->reactiveKey() }}" :column-chart-model="$catStackedChart" />
                </div>
                <table class="table table-bordered table-striped ">
                    <thead>
                        <tr>
                            <th>Категория</th>
                            <th>Итог</th>
                            @if (!empty($catHeader))
                                @foreach ($catHeader as $cashTitle)
                                    <th>{{ $cashTitle }}</th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($catSummary as $catName => $cashData)
                            <tr>
                                <td>
                                    <button wire:click="$set('selectedCategory', '{{ $catName }}')" class="btn btn-link p-0">
                                        {{ $catName }}
                                    </button>
                                </td>
                                <td>
                                    @php
                                        $totalsByCurrency = [];
                                        foreach ($cashData as $cashTitle => $value) {
                                            preg_match('/\((.*?)\)$/', $cashTitle, $matches);
                                            $currency = $matches[1] ?? '';
                                            $totalsByCurrency[$currency] = ($totalsByCurrency[$currency] ?? 0) + $value;
                                        }
                                    @endphp
                                    @foreach ($totalsByCurrency as $currency => $total)
                                        {{ $currency }}: {{ number_format($total, 2, '.', ' ') }}@if (!$loop->last)
                                            <br>
                                        @endif
                                    @endforeach
                                </td>
                                @foreach ($catHeader as $cashTitle)
                                    <td>{{ $cashData[$cashTitle] ?? '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div x-show="open" x-transition>
                <button wire:click="$set('selectedCategory', '')" class="btn btn-secondary mb-3">Назад к группировке</button>
                <h4>Детальная информация для категории "{{ $selectedCategory }}"</h4>
                <table class="table table-bordered table-striped mt-3">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Проект</th>
                            <th>Контрагенты</th>
                            <th>Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detailedCatRecords as $record)
                            <tr>
                                <td>{{ $record->date }}</td>
                                <td>{{ isset($record->project) ? $record->project->title : 'N/A' }}</td>
                                <td>{{ isset($record->object) ? $record->object->title : 'N/A' }}</td>
                                <td>{{ number_format($record->amount, 2, '.', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>


    <div class="card mb-4 shadow-sm p-3" x-data="{ open: true }">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="font-bold text-lg">Расходы по контрагентам:</h3>
            <button type="button" class="btn btn-outline btn-sm" @click="open = !open">
                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>
        <!-- Новый фильтр по контрагентам -->
        <div class="p-3">
            <select wire:model="selectedObj" class="form-select mb-3">
                <option value="">Все контрагенты</option>
                @foreach ($objFilterOptions as $objOption)
                    <option value="{{ $objOption }}">{{ $objOption }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="open" x-transition>
            <div style="height: 300px;">
                <livewire:livewire-column-chart key="{{ $objStackedChart->reactiveKey() }}" :column-chart-model="$objStackedChart" />
            </div>
            <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>Контрагент</th>
                        <th>Итог</th>
                        @if (!empty($objHeader))
                            @foreach ($objHeader as $cashTitle)
                                <th>{{ $cashTitle }}</th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($objSummary as $objTitle => $cashData)
                        <tr>
                            <td>{{ $objTitle }}</td>
                            <td>
                                @php
                                    $rowTotals = [];
                                    foreach ($cashData as $cashTitle => $value) {
                                        preg_match('/\((.*?)\)$/', $cashTitle, $matches);
                                        $currency = $matches[1] ?? 'TMT';
                                        $rowTotals[$currency] = ($rowTotals[$currency] ?? 0) + $value;
                                    }
                                @endphp
                                @foreach ($rowTotals as $currency => $total)
                                    {{ $currency }}: {{ number_format($total, 2, '.', ' ') }}@if (!$loop->last)
                                        <br>
                                    @endif
                                @endforeach
                            </td>
                            @foreach ($objHeader as $cashTitle)
                                <td>{{ $cashData[$cashTitle] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


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
                                <td>{{ $data['project'] }}</td>
                                <td>
                                    {{ number_format((float) $data['total'], 2, '.', ' ') }} {{ $data['currency'] }}
                                </td>
                            </tr>
                        @endforeach
                        @php
                            $projectTotals = [];
                            foreach ($projectData as $d) {
                                $projectTotals[$d['currency']] = ($projectTotals[$d['currency']] ?? 0) + $d['total'];
                            }
                        @endphp
                        <tr class="fw-bold">
                            <td>Итого</td>
                            <td>
                                @foreach ($projectTotals as $currency => $total)
                                    {{ $currency }}: {{ number_format((float) $total, 2, '.', ' ') }}
                                    @if (!$loop->last)
                                        <br>
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                    </tbody>
                </table>
            @else
                <p class="text-center">Данных для таблицы нет</p>
            @endif
        </div>
    </div>
</div>
