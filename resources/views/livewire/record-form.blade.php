<div class="mt-3">
    <div class="floating-button">
        <button class="btn btn-primary rounded-circle shadow" wire:click="openModal()">
            <i class="bi bi-plus-circle fs-4"></i>
        </button>
    </div>

    <style>
        .floating-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>

    <div class="row row-cols-6 g-3">
        @foreach ($templates as $template)
            <div class="col">
                <div class="card text-center shadow-sm">
                    <!-- Иконка шаблона -->
                    <div class="card-header bg-light">
                        <i class="{{ $template->icon }} fs-3"></i>
                    </div>

                    <!-- Название шаблона -->
                    <div class="card-body p-2">
                        <span class="small text-truncate d-block">{{ $template->title_template }}</span>
                    </div>

                    <!-- Кнопки управления -->
                    <div class="card-footer p-1">
                        <div class="d-flex justify-content-center gap-1">
                            <!-- Кнопка редактирования -->
                            <button class="btn btn-sm btn-outline-primary"
                                wire:click="openModal({{ $template->id }}, true)" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <!-- Кнопка удаления -->
                            <button class="btn btn-sm btn-outline-danger"
                                wire:click="deleteTemplate({{ $template->id }})" title="Удалить">
                                <i class="bi bi-trash"></i>
                            </button>
                            <!-- Кнопка применения -->
                            <button class="btn btn-sm btn-outline-secondary"
                                wire:click="applyTemplate({{ $template->id }})" title="Применить">
                                <i class="bi bi-plus-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>



    <div class="card mt-3">
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



                <button class="btn btn-sm btn-outline-secondary ms-3" id="toggle-card">
                    <span id="toggle-text">Скрыть</span>
                </button>
            </div>
        </div>
        <div class="card-body" id="card-body">
            <div>
                <!-- Выбор типа фильтра -->
                <select id="filterType" class="form-control" wire:model.live="filterType">
                    <option value="daily">За день</option>
                    <option value="weekly">За неделю</option>
                    <option value="monthly">За месяц</option>
                    <option value="custom">Пользовательский диапазон</option>
                </select>
            </div>

            <div>
                <!-- Поле для ввода даты за день -->
                @if ($filterType === 'daily')
                    <label for="dateFilter">Выберите день</label>
                    <input type="date" id="dateFilter" class="form-control" wire:model.lazy="dateFilter">
                @endif

                <!-- Поля для пользовательского диапазона -->
                @if ($filterType === 'custom')
                    <div class="row">
                        <div class="col">
                            <label for="startDate">Начало</label>
                            <input type="date" id="startDate" class="form-control" wire:model.live="startDate">
                        </div>
                        <div class="col">
                            <label for="endDate">Конец</label>
                            <input type="date" id="endDate" class="form-control" wire:model.live="endDate">
                        </div>
                    </div>
                @endif
            </div>
            <div class="row mt-2">
                <div class="col-3">
                    <div class="stat-box">
                        <h6>Приход</h6>
                        <p class="text-success fs-4">{{ $dailySummary['income'] }} Манат</p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-box">
                        <h6>Расход</h6>
                        <p class="text-danger fs-4">{{ $dailySummary['expense'] }} Манат</p>
                    </div>
                </div>


                <div class="col-3">
                    <div class="stat-box">
                        <h6>Текущий итоговый баланс</h6>
                        <p class="fs-4" style="color: {{ $totalBalance >= 0 ? 'green' : 'red' }}">
                            {{ $totalBalance }} Манат
                        </p>

                    </div>
                </div>
                @if ($showBalance)
                    <div class="col-3">
                        <div class="stat-box">
                            <h6>Баланс за выбранный день</h6>
                            <p class="text-primary fs-4">
                                {{ $dailySummary['balance'] }} Манат
                            </p>
                        </div>
                    </div>
                @endif

                <button class="btn btn-success" wire:click="closeCashRegister"
                    @if (\App\Models\CashRegister::whereDate('Date', $dateFilter ?: now()->format('Y-m-d'))->exists()) disabled @endif>
                    {{ \App\Models\CashRegister::whereDate('Date', $dateFilter ?: now()->format('Y-m-d'))->exists() ? 'Касса закрыта' : 'Закрыть кассу' }}
                </button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cardBody = document.getElementById('card-body');
            const toggleButton = document.getElementById('toggle-card');
            const toggleText = document.getElementById('toggle-text');

            // Проверяем состояние в localStorage
            const isHidden = localStorage.getItem('isCardHidden');
            if (isHidden === 'true') {
                cardBody.style.display = 'none';
                toggleText.textContent = 'Раскрыть';
            }

            // Обработчик клика
            toggleButton.addEventListener('click', () => {
                if (cardBody.style.display === 'none') {
                    cardBody.style.display = 'block';
                    toggleText.textContent = 'Скрыть';
                    localStorage.setItem('isCardHidden', 'false');
                } else {
                    cardBody.style.display = 'none';
                    toggleText.textContent = 'Раскрыть';
                    localStorage.setItem('isCardHidden', 'true');
                }
            });
        });
    </script>



    @if (session()->has('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="mb-3">
        @if (Auth::user()->is_admin)
            <label for="selectCashRegisterFilter">Выберите кассу</label>
            <select wire:model="selectedCashRegisterFilter" id="selectCashRegisterFilter" class="form-select">
                <option value="">Все кассы</option>
                @foreach ($availableCashRegisters as $cashRegister)
                    <option value="{{ $cashRegister->id }}">
                        Касса за {{ \Carbon\Carbon::parse($cashRegister->Date)->format('d.m.Y') }} — 
                        Баланс: {{ number_format($cashRegister->balance, 2, '.', ' ') }} Манат
                    </option>
                @endforeach
            </select>
        @endif
    </div>
    

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Тип статьи</th>
                <th>Описание статьи</th>
                <th>Сумма</th>
                <th>Клиент</th>
                <th>Валюта</th>
                <th>Дата</th>
                <th>Курс обмена</th>
                <th>Ссылка</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>

                    <td
                        class="@if ($record->ArticleType === 'Приход') bg-success text-white @elseif ($record->ArticleType === 'Расход') bg-danger text-white @endif">
                        {{ $record->ArticleType }}
                    </td>

                    <td>{{ $record->ArticleDescription }}</td>
                    <td>{{ number_format($record->Amount, 2, '.', ' ') }}</td>
                    <td>
                        @if ($record->Object)
                            {{ $record->Object }}
                    </td>
                @else
                    <span>Нет клиента</span>
            @endif
            <td>{{ $record->Currency }}</td>
            <td>{{ $record->Date }}</td>
            <td>{{ number_format($record->ExchangeRate, 2, '.', ' ') }}</td>
            <td>
                @if ($record->Link)
                    <a href="{{ $record->Link }}" target="_blank">Ссылка</a>
                @else
                    <span>Нет ссылки</span>
                @endif
            </td>

            <td>
                @if (Auth::check() && Auth::user()->is_admin)
                    <i class="bi bi-trash text-danger ms-3" role="button"
                        wire:click="deleteRecord({{ $record->id }})"></i>
                @endif
                <i class="bi bi-pencil-square text-warning" role="button"
                    wire:click="openModal({{ $record->id }})"></i>
                <i class="bi bi-files ms-3 text-primary" role="button" wire:click="copyRecord({{ $record->id }})"
                    title="Копировать"></i>
            </td>

            </tr>
            @endforeach
        </tbody>




    </table>
    <div class="mt-4">
        {{ $records->links() }}
    </div>
    <!-- Модальное окно -->
    <div class="modal @if ($isModalOpen) d-block @else d-none @endif" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title">
                        {{ $recordId ? 'Редактировать запись' : 'Добавить запись' }}
                    </span>

                    <button type="button" class="btn-close" wire:click="closeModal" aria-label="Закрыть"></button>

                </div>
                <div class="modal-body">
                    @if (session()->has('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form wire:submit.prevent="submit">
                        @php
                            $isCashClosed = \App\Models\CashRegister::whereDate(
                                'Date',
                                $dateFilter ?: now()->format('Y-m-d'),
                            )->exists();
                        @endphp
                        <div x-data="{ suggestions: @entangle('suggestions'), query: @entangle('object') }" class="mb-3">
                            <input id="object" type="text" class="form-control" x-model="query"
                                placeholder="Введите объект/клиента">

                            <!-- Список совпадений -->
                            <ul class="list-group mt-2" x-show="suggestions.length > 0">
                                <li class="list-group-item list-group-item-action" x-on:click="query = suggestion"
                                    x-for="suggestion in suggestions" x-text="suggestion">
                                </li>
                            </ul>
                        </div>


                        <div class="mb-3">
                            <select wire:model="articleType" id="articleType" class="form-select">
                                <option value="">Выберите тип статьи</option>
                                <option value="Приход">Приход</option>
                                <option value="Расход">Расход</option>
                            </select>
                            @error('articleType')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="cashRegister">Касса</label>
                            <select wire:model="selectedCashRegister" id="cashRegister" class="form-select">
                                <option value="" disabled selected>Выберите кассу</option>
                                <!-- Добавляем значение по умолчанию -->
                                @foreach ($availableCashRegisters as $cashRegister)
                                    <option value="{{ $cashRegister->id }}">
                                        Касса за {{ \Carbon\Carbon::parse($cashRegister->Date)->format('d.m.Y') }} —
                                        Баланс: {{ number_format($cashRegister->balance, 2, '.', ' ') }} Манат
                                    </option>
                                @endforeach
                            </select>
                            @error('selectedCashRegister')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>




                        <div class="mb-3">
                            <textarea wire:model="articleDescription" id="articleDescription" class="form-control"
                                placeholder="Введите описание статьи"></textarea>
                            @error('articleDescription')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <input wire:model="amount" id="amount" type="number" class="form-control"
                                placeholder="Введите сумму">
                            @error('amount')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>



                        <div class="mb-3">
                            <input wire:model="date" id="date" type="date" class="form-control">
                            @error('date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <select wire:model.live="currency" id="currency" class="form-select">
                                <option value="">Выберите валюту</option>
                                <option value="Манат">Манат</option>
                                <option value="Доллар">Доллар</option>
                                <option value="Рубль">Рубль</option>
                                <option value="Юань">Юань</option>
                            </select>
                            @error('currency')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <input type="text" wire:model.live="exchangeRate" id="exchangeRate"
                                class="form-control" placeholder="Курс будет подставлен автоматически">
                            @error('exchangeRate')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <input type="text" wire:model="link" id="link" class="form-control"
                                placeholder="Ссылка на курс">
                            @error('link')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isTemplate"
                                wire:model.live="isTemplate">
                            <label class="form-check-label" for="isTemplate">Сохранить как шаблон</label>
                        </div>

                        @if ($isTemplate)
                            <div class="mb-3">
                                <input type="text" id="titleTemplate" class="form-control"
                                    wire:model.live="titleTemplate" placeholder="Введите название шаблона">
                                @error('titleTemplate')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>


                            <div>

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                        id="iconDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        @if ($icon)
                                            <i class="{{ $icon }} fs-5"></i> {{ $icon }}
                                        @else
                                            Выберите иконку
                                        @endif
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="iconDropdown">
                                        @foreach ($availableIcons as $availableIcon)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center" href="#"
                                                    wire:click.prevent="$set('icon', '{{ $availableIcon }}')">
                                                    <i class="{{ $availableIcon }} me-2 fs-5"></i>
                                                    {{ $availableIcon }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i></button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
