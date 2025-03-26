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


    <!-- filepath: d:\OSPanel\domains\financeData\resources\views\livewire\dashboard.blade.php -->
    <div class="card mb-4 shadow-sm p-3" x-data="{ open: true }">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="font-bold text-lg">Расходы по категориям:</h3>
            <button type="button" class="btn btn-outline btn-sm" @click="open = !open">
                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>
        <div class="p-3">
            <select wire:model.live="selectedCategory" class="form-select mb-3">
                <option value="">Все категории</option>
                @foreach ($catFilterOptions as $catOption)
                    <option value="{{ $catOption }}">{{ $catOption }}</option>
                @endforeach
            </select>
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
                                <td>{{ $catName }}</td>
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
                <h4>Детальная информация для категории "{{ $selectedCategory }}"</h4>

                @if (isset($availableMonths) && count($availableMonths) > 0)
                    <div class="mb-3">
                        <label for="selectedMonth" class="form-label">Выберите месяц:</label>
                        <select wire:model.live="selectedMonth" id="selectedMonth" class="form-select">
                            @foreach ($availableMonths as $monthKey => $monthLabel)
                                <option value="{{ $monthKey }}">{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($catMonthlyChart)
                    <div style="height: 300px;">
                        <livewire:livewire-column-chart key="{{ $catMonthlyChart->reactiveKey() }}"
                            :column-chart-model="$catMonthlyChart" />
                    </div>
                @endif

                <div class="mt-3">
                    <h5>Данные за {{ $availableMonths[$selectedMonth] ?? '' }}</h5>
                    <table class="table table-bordered table-striped">
                        <tfoot>
                            <tr>
                                <th>Проекты</th>
                                <td colspan="2">
                                    @php
                                        $projects = $detailedCatRecords
                                            ->map(function ($record) {
                                                return isset($record->project) ? $record->project->title : 'N/A';
                                            })
                                            ->unique();
                                    @endphp
                                    {{ $projects->implode(', ') }}
                                </td>
                            </tr>
                            <tr>
                                <th>Контрагенты</th>
                                <td colspan="2">
                                    @php
                                        $counteragents = $detailedCatRecords
                                            ->map(function ($record) {
                                                return isset($record->object) ? $record->object->title : 'N/A';
                                            })
                                            ->unique();
                                    @endphp
                                    {{ $counteragents->implode(', ') }}
                                </td>
                            </tr>
                            <tr class="fw-bold">
                                <th>Итоговая сумма</th>
                                <td colspan="2">
                                    {{ number_format($detailedCatRecords->sum('amount'), 2, '.', ' ') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
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
{{-- <script>
    document.getElementById('downloadPdf').addEventListener('click', function () {
        html2canvas(document.querySelector('.pdf-container')).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jspdf.jsPDF('p', 'mm', 'a4');
            const imgWidth = 210;
            const pageHeight = 295;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            pdf.save('dashboard.pdf');
        });
    });
</script> --}}
