<div class="container mt-4">
    <!-- Фильтры: даты и категории (как ранее) -->
    <div class="row mb-3">
        <div class="col-md-3">
            <label for="startDate" class="form-label">Начальная дата (Период 1)</label>
            <input type="date" id="startDate" class="form-control" wire:model.live="startDate">
        </div>
        <div class="col-md-3">
            <label for="endDate" class="form-label">Конечная дата (Период 1)</label>
            <input type="date" id="endDate" class="form-control" wire:model.live="endDate">
        </div>
        <!-- Dropdown с чекбоксами для категорий -->
        <div class="col-md-3">
            <label class="form-label">Категории</label>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Выберите категории
                </button>
                <div class="dropdown-menu p-2" style="max-height: 200px; overflow-y: auto;">
                    @foreach ($allCategories as $catId => $catTitle)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="{{ $catId }}"
                                id="cat_{{ $catId }}" wire:model.live="selectedCategories">
                            <label class="form-check-label" for="cat_{{ $catId }}">{{ $catTitle }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры: даты Периода 2 -->
    <div class="row mb-3">
        <div class="col-md-3">
            <label for="compareStartDate" class="form-label">Нач. дата (Период 2)</label>
            <input type="date" id="compareStartDate" class="form-control" wire:model.live="compareStartDate">
        </div>
        <div class="col-md-3">
            <label for="compareEndDate" class="form-label">Кон. дата (Период 2)</label>
            <input type="date" id="compareEndDate" class="form-control" wire:model.live="compareEndDate">
        </div>
    </div>

    <!-- Активные фильтры (категории, проекты, кассы, объекты) -->
    @php
        $hasFilters =
            !empty($selectedCategories) ||
            !empty($filterProjectIds) ||
            !empty($filterCashIds) ||
            !empty($filterObjectIds);
    @endphp
    @if ($hasFilters)
        <div class="mb-3">
            <strong>Активные фильтры:</strong>
            <!-- Категории -->
            @foreach ($selectedCategories as $catId)
                <span class="badge bg-info text-dark me-2">
                    {{ $allCategories[$catId] ?? 'Без категории' }}
                    <a href="#" class="text-dark ms-1"
                        wire:click.prevent="toggleCategoryFilter(@js($catId))">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </span>
            @endforeach

            <!-- Проекты -->
            @foreach ($filterProjectIds as $pId)
                <span class="badge bg-warning text-dark me-2">
                    {{ $allProjects[$pId] ?? '??' }}
                    <a href="#" class="ms-1 text-dark"
                        wire:click.prevent="toggleProjectFilter({{ $pId }})">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </span>
            @endforeach
            <!-- Кассы -->
            @foreach ($filterCashIds as $cashId)
                <span class="badge bg-secondary text-light me-2">
                    {{ $allCashes[$cashId] ?? '??' }}
                    <a href="#" class="ms-1 text-light"
                        wire:click.prevent="toggleCashFilter({{ $cashId }})">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </span>
            @endforeach
            <!-- Объекты -->
            @foreach ($filterObjectIds as $objId)
                <span class="badge bg-primary text-light me-2">
                    {{ $allObjects[$objId] ?? '??' }}
                    <a href="#" class="ms-1 text-light"
                        wire:click.prevent="toggleObjectFilter({{ $objId }})">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </span>
            @endforeach
        </div>
    @endif
    @php
        // ключ меняется при любом сдвиге фильтров
        $pieKey = md5($pieChartModel->reactiveKey() . $startDate . $endDate);
    @endphp
    <!-- Диаграмма: PieChart по категориям (Период 1) -->
    <div class="card mb-4 p-3" id="chart1">
        <h5>Расходы по категориям (Период 1)</h5>
        <p class="text-muted mb-2" style="font-size: 1.2rem; font-weight: bold; color: #d9534f !important;">Общая сумма:
            {{ number_format($totalAmount, 2, '.', ' ') }}TMT</p>
        <div style="height: 350px;">
            <livewire:livewire-pie-chart key="pie-{{ $pieKey }}" :pie-chart-model="$pieChartModel" />
        </div>
    </div>

    <!-- Таблица (Период 1) -->
    <div id="table1">
        <h4>Таблица (Период 1)</h4>
        @if (count($selectedCategories) !== 1)
            <table class="table table-bordered mb-5">
                <thead>
                    <tr>
                        <th>Категория</th>
                        {{-- <th>Кол-во транз.</th> --}}
                        <th class="text-end">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categorySummary as $row)
                        <tr>
                            <td>
                                <a href="#"
                                    wire:click.prevent="toggleCategoryFilter(@js($row['cat_id']))">
                                    {{ $row['title'] }}
                                </a>

                            </td>
                            {{-- <td>{{ $row['trx_count'] }}</td> --}}
                            <td class="text-end">
                                {{ number_format($row['amount'], 2, '.', ' ') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="1" class="text-end">Итого</th>
                        <th class="text-end">{{ number_format($totalAmount, 2, '.', ' ') }}</th>
                    </tr>
                </tfoot>
            </table>

            {{--  ► выбрана ровно одна категория – детализация транзакций  --}}
        @else
            <table class="table table-bordered mb-5">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Касса</th>
                        <th>Описание</th>
                        <th>Проект</th>
                        <th>Объект</th>

                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $rec)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($rec->date)->format('d.m.Y') }}</td>
                            <td>
                                @if ($rec->cash)
                                    <a href="#" wire:click.prevent="toggleCashFilter({{ $rec->cash->id }})">
                                        {{ $rec->cash->title }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="desc-cell" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ $rec->description }}">
                                    {{ $rec->description }}
                                </span>
                            </td>
                            <td>
                                @if ($rec->project)
                                    <a href="#"
                                        wire:click.prevent="toggleProjectFilter({{ $rec->project->id }})">
                                        {{ $rec->project->title }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if ($rec->object)
                                    <a href="#" wire:click.prevent="toggleObjectFilter({{ $rec->object->id }})">
                                        {{ $rec->object->title }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                {{ number_format($rec->amount, 2, '.', ' ') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Итого</th>
                        <th class="text-end">{{ number_format($totalAmount, 2, '.', ' ') }}</th>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <!-- Диаграмма сравнения (Период 1 vs Период 2) по категориям -->
    <div class="card mb-4 p-3" id="chart2">
        <h5>Сравнение по категориям (Период 1 vs. Период 2)</h5>
        <p class="text-muted mb-2" style="font-size: 1.2rem; font-weight: bold; color: #d9534f !important;">
            Период 2 Сумма: {{ number_format($compareTotalAmount, 2, '.', ' ') }}
        </p>
        <div style="height: 400px;">
            <livewire:livewire-column-chart :column-chart-model="$compareChart" key="{{ $compareChart->reactiveKey() }}" />
        </div>
    </div>

    <!-- Таблица (Период 2) -->
    <div id="table2">
        <h4>Таблица (Период 2)</h4>

        {{-- ▼ 0 или >1 категорий – агрегированная сводка --}}
        @if (count($selectedCategories) !== 1)
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Категория</th>
                        <th class="text-end">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($compareCategorySummary as $row)
                        <tr>
                            <td>
                                <a href="#"
                                    wire:click.prevent="toggleCategoryFilter(@js($row['cat_id']))">
                                    {{ $row['title'] }}
                                </a>

                            </td>
                            <td class="text-end">
                                {{ number_format($row['amount'], 2, '.', ' ') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-end">Итого</th>
                        <th class="text-end">{{ number_format($compareTotalAmount, 2, '.', ' ') }}</th>
                    </tr>
                </tfoot>
            </table>

            {{-- ▼ выбрана ровно одна категория – детальные транзакции --}}
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Касса</th>
                        <th>Описание</th>
                        <th>Проект</th>
                        <th>Объект</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($compareRecords as $rec2)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($rec2->date)->format('d.m.Y') }}</td>
                            <td>
                                @if ($rec2->cash)
                                    <a href="#" wire:click.prevent="toggleCashFilter({{ $rec2->cash->id }})">
                                        {{ $rec2->cash->title }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="desc-cell" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ $rec->description }}">
                                    {{ $rec->description }}
                                </span>
                            </td>
                            <td>
                                @if ($rec2->project)
                                    <a href="#"
                                        wire:click.prevent="toggleProjectFilter({{ $rec2->project->id }})">
                                        {{ $rec2->project->title }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if ($rec2->object)
                                    <a href="#"
                                        wire:click.prevent="toggleObjectFilter({{ $rec2->object->id }})">
                                        {{ $rec2->object->title }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                {{ number_format($rec2->amount, 2, '.', ' ') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Итого</th>
                        <th class="text-end">{{ number_format($compareTotalAmount, 2, '.', ' ') }}</th>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <!-- Новый график сравнения по кассам для выбранной категории (если ровно одна категория выбрана) -->
    @if ($cashComparisonChart)
        <div class="card mb-4 p-3" id="chart3">
            <h5>Сравнение по кассам (категория: {{ $allCategories[reset($selectedCategories)] ?? 'Неизвестно' }})</h5>
            <div style="height: 400px;">
                <livewire:livewire-column-chart :column-chart-model="$cashComparisonChart" key="{{ $cashComparisonChart->reactiveKey() }}" />
            </div>
        </div>

        <!-- Таблица сравнения по кассам -->
        @if ($cashComparisonTableData)
            <div class="card mb-4 p-3" id="table3">
                <h5>Таблица: Расход по кассам (категория:
                    {{ $allCategories[reset($selectedCategories)] ?? 'Неизвестно' }})</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Касса</th>
                            <th>Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cashComparisonTableData as $cashId => $sum)
                            <tr>
                                <td>{{ $allCashes[$cashId] ?? 'Неизвестно' }}</td>
                                <td>{{ number_format($sum, 2, '.', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    <div class="floating-menu"
        style="position: fixed; bottom: 10px; right: 10px; background: #fff; border:1px solid #ccc; padding:10px; z-index:999;">
        <strong>Быстрый переход:</strong>
        <ul>
            <li><a href="#chart1">Период 1</a></li>
            <li><a href="#chart2">Сравнение (P1 vs P2)</a></li>
            <li><a href="#chart3">Сравнение по кассам</a></li>
        </ul>
        <hr>
        <strong>Скрыть/показать:</strong>
        <ul>
            <li><label><input type="checkbox" id="toggleChart1" checked> Период 1</label></li>
            <li><label><input type="checkbox" id="toggleChart2" checked> Сравнение P1 vs P2</label></li>
            <li><label><input type="checkbox" id="toggleChart3" checked> Сравнение по кассам</label></li>
        </ul>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggles = [{
                    checkboxId: 'toggleChart1',
                    targets: ['chart1', 'table1']
                },
                {
                    checkboxId: 'toggleChart2',
                    targets: ['chart2', 'table2']
                },
                {
                    checkboxId: 'toggleChart3',
                    targets: ['chart3', 'table3']
                },
            ];
            toggles.forEach(({
                checkboxId,
                targets
            }) => {
                const cb = document.getElementById(checkboxId);
                const stored = localStorage.getItem(checkboxId);
                if (stored === 'false') {
                    cb.checked = false;
                    targets.forEach(id => document.getElementById(id)?.style.setProperty('display',
                        'none'));
                }
                cb.addEventListener('change', () => {
                    localStorage.setItem(checkboxId, cb.checked);
                    targets.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.style.display = cb.checked ? '' : 'none';
                    });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const tooltipTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="tooltip"]')
            );
            tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
        });
    </script>
    <style>
        /* фиксируем ширину/высоту ячейки и прячем переполнение */
        .desc-cell {
            max-width: 220px;   /* можно подправить */
            max-height: 38px;   /* ≈ две строки bootstrap */
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
    </style>
</div>
