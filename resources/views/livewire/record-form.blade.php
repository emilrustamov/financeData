<div class="mt-3">
    <x-alerts />
    <div class="floating-buttons"
        style="position: fixed; bottom: 20px; right: 20px; display: flex; flex-direction: column; gap: 10px;">
        <button class="btn btn-success rounded-circle shadow" wire:click="openForm()">
            <i class="bi bi-plus-circle fs-4"></i>
        </button>
    </div>
    @if ($myCashes->count() > 0)
        <h4>Мои кассы</h4>
        @if ($myCashes->count() <= 6)
            <div class="row">
                @foreach ($myCashes as $cash)
                    <div class="col-md-2 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">{{ $cash->title }}</h5>
                                <p class="card-text" style="color: {{ $cash->balance > 0 ? 'green' : ($cash->balance < 0 ? 'red' : '#ffca2c') }}">
                                    {{ number_format($cash->balance, 2, '.', ' ') }} TMT
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div id="cashCarousel" class="carousel slide" data-bs-interval="false">
                <div class="carousel-inner">
                    @foreach ($myCashes->chunk(6) as $chunkIndex => $cashChunk)
                        <div class="carousel-item {{ $chunkIndex == 0 ? 'active' : '' }}">
                            <div class="row">
                                @foreach ($cashChunk as $cash)
                                    <div class="col-md-2 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">{{ $cash->title }}</h5>
                                                <p class="card-text" style="color: {{ $cash->balance > 0 ? 'green' : ($cash->balance < 0 ? 'red' : '#ffca2c') }}">
                                                    {{ number_format($cash->balance, 2, '.', ' ') }} TMT
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#cashCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#cashCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        @endif
    @endif

    <div class="card mt-3" x-data="{ open: true }">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="me-3 mb-0">
                    @if (!$filterType || $filterType === 'daily')
                        Выбранная дата:
                        {{ $dateFilter ? 'Баланс за день: ' . Carbon\Carbon::parse($dateFilter)->format('d.m.Y') : 'Баланс за сегодня с записями за все время' }}
                    @elseif ($filterType === 'weekly')
                        Показывается информация за неделю: {{ now()->startOfWeek()->format('d.m.Y') }} -
                        {{ now()->endOfWeek()->format('d.m.Y') }}
                    @elseif ($filterType === 'monthly')
                        Показывается информация за месяц: {{ now()->startOfMonth()->format('d.m.Y') }} -
                        {{ now()->endOfMonth()->format('d.m.Y') }}
                    @elseif ($filterType === 'custom' && $startDate && $endDate)
                        Показывается информация за диапазон: {{ $startDate }} - {{ $endDate }}
                    @else
                        Баланс за сегодня с записями за все время
                    @endif
                </h5>

                <button class="btn btn-sm btn-outline-secondary ms-3" @click="open = !open">
                    <span x-text="open ? 'Скрыть' : 'Показать'"></span>
                </button>
            </div>
        </div>
        <div class="card-body" x-show="open">
            <div class="row g-3">
                <div class="col-md-3">
                    <select wire:model.change="cashRegFltr" class="form-control">
                        <option value="">Выберите кассу</option>
                        @foreach ($cashRegisters as $cash)
                            <option value="{{ $cash->id }}">{{ $cash->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control mb-3" placeholder="Поиск по описанию или клиенту"
                        wire:model.live.debounce:250="searchTerm">
                </div>
                <div class="col-md-3">
                    <select id="filterType" class="form-control" wire:model.change="filterType">
                        <option value="daily">За день</option>
                        <option value="weekly">За неделю</option>
                        <option value="monthly">За месяц</option>
                        <option value="custom">Пользовательский диапазон</option>
                        <option value="all">За все время</option>
                    </select>
                </div>
                <div class="col-md-3">

                    @if ($filterType === 'daily')
                        <input type="date" id="dateFilter" class="form-control" wire:model.lazy="dateFilter"
                            max="{{ date('Y-m-d') }}">
                    @endif

                    @if ($filterType === 'custom')
                        <div class="row">
                            <div class="col">
                                <input type="date" id="startDate" class="form-control" wire:model="startDate"
                                    max="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col">
                                <input type="date" id="endDate" class="form-control" wire:model="endDate"
                                    max="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    @endif
                </div>

            </div>
            <div class="row mt-2">
                <div class="col-3">
                    <div class="stat-box">
                        <h6>Приход</h6>
                        <p class="text-success fs-4">{{ $dailySummary['income'] }} <span>TMT</span> </p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-box">
                        <h6>Расход</h6>
                        <p class="text-danger fs-4">{{ $dailySummary['expense'] }} <span>TMT</span> </p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-box">
                        <h6>Текущий итоговый баланс</h6>
                        <p class="fs-4" style="color: {{ $dailySummary['totalBalance'] >= 0 ? 'green' : 'red' }}">
                            {{ number_format($dailySummary['totalBalance'], 1) }} <span>TMT</span>
                        </p>
                    </div>
                </div>

                @if ($showBalance)
                    <div class="col-3">
                        <div class="stat-box">
                            <h6>Баланс за выбранный день</h6>
                            <p class="fs-4" style="color: {{ $dailySummary['balance'] >= 0 ? 'green' : 'red' }}">
                                {{ number_format($dailySummary['balance'], 1) }} <span>TMT</span>
                            </p>
                        </div>
                    </div>
                @endif

                <div class="d-flex align-items-center">
                    <div class="btn-group">
                        <button class="btn {{ $isCashClosed ? 'btn-warning' : 'btn-success' }}"
                            wire:click="closeCashRegister" @if ($this->canCloseCashRegister['disabled']) disabled @endif>
                            {{ $this->canCloseCashRegister['message'] }}
                        </button>

                        @if ($isCashClosed)
                            <button class="btn btn-success" role="button"
                                onclick="if(confirm('Вы действительно хотите открыть кассу?')) { @this.call('openCashRegister') }">
                                <i class="bi bi-unlock"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="typeDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Тип статьи
                            @if ($typeFilter !== null)
                                ({{ $typeFilter }})
                            @endif
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="typeDropdown">
                            <li><a class="dropdown-item" href="#"
                                    wire:click.prevent="$set('typeFilter', null)">Все</a></li>
                            <li><a class="dropdown-item" href="#"
                                    wire:click.prevent="$set('typeFilter', 1)">Приход</a></li>
                            <li><a class="dropdown-item" href="#"
                                    wire:click.prevent="$set('typeFilter', 0)">Расход</a></li>
                        </ul>
                    </div>
                </th>
                <th>Описание статьи</th>
                <th>Сумма</th>
                <th>Проект</th>
                <th>Контрагент</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
        </thead>
        <!-- filepath: /d:/OSPanel/domains/financeData/resources/views/livewire/record-form.blade.php -->
        <tbody>
            @foreach ($records as $record)
                @if (!$typeFilter || $record->type === $typeFilter)
                    <tr>
                        <td
                            class="@if ($record->type === 1) bg-success text-white @elseif ($record->type === 0) bg-danger text-white @endif">
                            {{ $record->type === 1 ? 'Приход' : 'Расход' }}
                        </td>
                        <td>{{ $record->description }}</td>
                        <td>{{ number_format($record->amount, 2, '.', ' ') }}</td>
                        <td>{{ $record->project ? $record->project->title : '-' }}</td>
                        <td>{{ $record->object ? $record->object->title : '-' }}</td>
                        <td>{{ $record->date }}</td>
                        <td>
                            @can('edit transactions')
                                <button class="btn btn-sm btn-warning" wire:click="openForm({{ $record->id }})">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            @endcan
                            <button class="btn btn-sm btn-primary" wire:click="copyRecord({{ $record->id }})"
                                title="Копировать">
                                <i class="bi bi-files"></i>
                            </button>
                            @can('delete transactions')
                                <button class="btn btn-sm btn-danger"
                                    wire:click="confirmDeleteRecord({{ $record->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endcan
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $records->links() }}
    </div>

    <div class="modal fade @if ($showForm) show @endif" tabindex="-1"
        style="@if ($showForm) display: block; @else display: none; @endif" aria-modal="true"
        role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title">
                        {{ $recordId ? 'Редактировать' : 'Добавить' }} запись
                    </span>
                    <button type="button" class="btn-close" wire:click="closeForm" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="createRecord">
                        @if ($isCashClosedRecord)
                            <div class="alert alert-warning">
                                Касса закрыта. Изменения не могут быть сохранены.
                            </div>
                        @endif

                        <div class="mb-3 d-flex align-items-center">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            <select wire:model.change="type" id="type" class="form-select"
                                style="background-color: {{ $type == '1' ? 'lightgreen' : ($type == '0' ? 'lightcoral' : 'white') }}"
                                @if ($isCashClosedRecord) disabled @endif>
                                <option value="">Выберите тип статьи</option>
                                <option value="1">Приход</option>
                                <option value="0">Расход</option>
                            </select>
                        </div>
                        <div class="mb-3 d-flex align-items-center">
                            <i class="bi bi-currency-dollar me-2"></i>
                            <input wire:model="amount" id="amount" type="number" class="form-control"
                                placeholder="Введите сумму" @if ($isCashClosedRecord) disabled @endif>
                        </div>
                        <div class="mb-3 d-flex align-items-center">
                            <i class="bi bi-card-text me-2"></i>
                            <textarea wire:model="desc" id="desc" class="form-control" placeholder="Введите описание статьи"
                                @if ($isCashClosedRecord) disabled @endif></textarea>
                        </div>
                        <div class="mb-3 d-flex align-items-center">
                            <i class="bi bi-tags me-2"></i>
                            <select wire:model.change="category" id="objectCategory" class="form-select"
                                @if ($isCashClosedRecord) disabled @endif>
                                <option value="">Выберите категорию</option>
                                @foreach ($objectCategories as $ctg)
                                    <option value="{{ $ctg->id }}"
                                        {{ $category == $ctg->id ? 'selected' : '' }}>
                                        {{ $ctg->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if ($category)
                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-person me-2"></i>
                                <select wire:model.live="object" id="object" class="form-select"
                                    @if ($isCashClosedRecord) disabled @endif>
                                    <option value="">Выберите контрагента</option>
                                    @foreach ($this->filteredObjects as $obj)
                                        <option value="{{ $obj->id }}"
                                            {{ $object == $obj->id ? 'selected' : '' }}>
                                            {{ $obj->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="mb-3 d-flex align-items-center">
                            <i class="bi bi-briefcase me-2"></i>
                            <select wire:model="project" id="project" class="form-select"
                                @if ($isCashClosedRecord) disabled @endif>
                                <option value="">Выберите проект</option>
                                @foreach ($projects as $proj)
                                    <option value="{{ $proj->id }}"
                                        {{ $project == $proj->id ? 'selected' : '' }}>
                                        {{ $proj->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 d-flex align-items-center">
                            <i class="bi bi-calendar me-2"></i>
                            @can('edit date')
                                <input wire:model="date" id="date" type="date" class="form-control"
                                    max="{{ date('Y-m-d') }}" @if ($isCashClosedRecord) disabled @endif>
                            @else
                                <input wire:model="date" id="date" type="date" class="form-control"
                                    min="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" readonly
                                    @if ($isCashClosedRecord) disabled @endif>
                            @endcan
                        </div>
                        <div class="mb-3 d-flex align-items-center">
                            <i class="bi bi-cash me-2"></i>
                            <select wire:model="cashID" id="cashID" class="form-select"
                                @if ($isCashClosedRecord) disabled @endif>
                                <option value="">Выберите кассу</option>
                                @foreach ($cashRegisters as $cash)
                                    <option value="{{ $cash->id }}">
                                        Касса: {{ $cash->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            @if (!$isCashClosedRecord)
                                <button type="submit" class="btn btn-success"><i
                                        class="bi bi-plus-circle"></i></button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    window.addEventListener('show-delete-record-confirmation', () => {
        if (confirm('Вы уверены, что хотите удалить запись?')) {
            @this.call('deleteRecord');
        }
    });

    window.addEventListener('show-delete-record-error', () => {
        alert('Касса закрыта. Запись не может быть удалена.');
    });

    window.addEventListener('show-delete-record-success', () => {
        alert('Запись успешно удалена.');
    });
</script>

<script>
    window.addEventListener('show-delete-template-confirmation', () => {
        if (confirm('Вы уверены, что хотите удалить этот шаблон?')) {
            @this.call('deleteTemplate');
        }
    });
</script>
