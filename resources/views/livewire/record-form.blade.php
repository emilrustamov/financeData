<div class="mt-3">
    <div class="row mb-3">
        <div class="col">
            <button class="btn btn-primary " wire:click="openModal()"> <i class="bi bi-plus-circle"></i> </button>
        </div>
        @foreach ($templates as $template)
    <div class="col text-center">
        <!-- Иконки редактирования и удаления -->
        <div class="d-flex justify-content-center mb-1">
            <!-- Кнопка редактирования -->
            <button class="btn btn-sm btn-outline-primary me-1" wire:click="openModal({{ $template->id }}, true)" title="Редактировать">
                <i class="bi bi-pencil"></i>
            </button>

            <!-- Кнопка удаления -->
            <button class="btn btn-sm btn-outline-danger" wire:click="deleteTemplate({{ $template->id }})" title="Удалить">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <!-- Кнопка применения шаблона -->
        <button class="btn btn-outline-secondary" wire:click="applyTemplate({{ $template->id }})">
            <i class="{{ $template->icon }}"></i>
        </button>
        <span class="small mt-1 d-block">{{ $template->title_template }}</span>
    </div>
@endforeach

    
    </div>


    {{-- <div class="input-group mb-3">
        <input type="date" class="form-control" wire:model.live="dateFilter">
        <button type="button" class="btn btn-outline-secondary" wire:click="resetDateFilter">Сбросить дату</button>
    </div> --}}


    {{-- <div class="card">
        <div class="card-header">Баланс на день</div>
        <div class="card-body">
            <p>Начальный баланс: {{ $dailyBalance['initial_balance'] }}</p>
            <p>Приход: {{ $dailyBalance['income'] }}</p>
            <p>Расход: {{ $dailyBalance['expense'] }}</p>
            <p>Итоговый баланс: {{ $dailyBalance['total_balance'] }}</p>
        </div>
    </div> --}}




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
                    <i class="bi bi-pencil-square text-warning" role="button"
                        wire:click="openModal({{ $record->id }})"></i>
                    <i class="bi bi-trash text-danger ms-3" role="button"
                        wire:click="deleteRecord({{ $record->id }})"></i>
                @endif
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
                    <form wire:submit.prevent="submit">

                        <div x-data="{ suggestions: @entangle('suggestions'), query: @entangle('object') }" class="mb-3">
                            <input id="object" type="text" class="form-control" x-model="query"
                                placeholder="Введите объект">

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
                            <button type="submit" class="btn btn-primary">Сохранить</button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
