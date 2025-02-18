<div>
    <div class="mt-1">
        <div class="floating-button2">
            <button class="btn btn-primary rounded-circle shadow" wire:click="openForm()" title="Добавить шаблон">
                <i class="bi bi-file-earmark-plus fs-4"></i>
            </button>
        </div>
    
        <div class="row row-cols-6 g-3">
            @foreach ($templates as $template)
                <div class="col">
                    <div class="card text-center shadow-sm">
                        <div class="card-header bg-light">
                            <i class="{{ $template->icon }} fs-3"></i>
                        </div>
                        <div class="card-body p-2">
                            <span class="small text-truncate d-block">{{ $template->title_template }}</span>
                        </div>

                        <div class="card-footer p-1">
                            <div class="d-flex justify-content-center gap-1">

                                <button class="btn btn-sm btn-outline-primary"
                                    wire:click="openForm({{ $template->id }})" title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <button class="btn btn-sm btn-outline-danger"
                                    wire:click="deleteTemplate({{ $template->id }})" title="Удалить">
                                    <i class="bi bi-trash"></i>
                                </button>

                                <button class="btn btn-sm btn-outline-success"
                                    wire:click="addRecordFromTemplate({{ $template->id }})" title="Добавить запись">
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <x-alerts />

        <div class="modal fade @if ($showForm) show @endif" tabindex="-1"
            style="@if ($showForm) display: block; @else display: none; @endif" aria-modal="true"
            role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">

                        <span class="modal-title">
                            {{ $templateId ? 'Редактировать шаблон' : 'Добавить шаблон' }}
                        </span>

                        <button type="button" class="btn-close" wire:click="closeForm" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="createTemplate">
                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                <select wire:model.live="type" id="type" class="form-select">
                                    <option value="">Выберите тип статьи</option>
                                    <option value="1">Приход</option>
                                    <option value="0">Расход</option>
                                </select>
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-currency-dollar me-2"></i>
                                <input wire:model="amount" id="amount" type="number" class="form-control"
                                    placeholder="Введите сумму">
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-card-text me-2"></i>
                                <textarea wire:model="desc" id="desc" class="form-control" placeholder="Введите описание статьи"></textarea>
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-tags me-2"></i>
                                <select wire:model.live="category" id="category" class="form-select">
                                    <option value="">Выберите категорию</option>
                                    @foreach ($objectCategories as $ctg)
                                        <option value="{{ $ctg->id }}"
                                            {{ $category == $ctg->id ? 'selected' : '' }}>
                                            {{ $ctg->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-person me-2"></i>
                                <select wire:model="object" id="object" class="form-select">
                                    <option value="">Выберите контрагента</option>
                                    @foreach ($objects as $obj)
                                        <option value="{{ $obj->id }}"
                                            {{ $object == $obj->id ? 'selected' : '' }}>
                                            {{ $obj->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-briefcase me-2"></i>
                                <select wire:model="project" id="project" class="form-select">
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
                                        max="{{ date('Y-m-d') }}">
                                @else
                                    <input wire:model="date" id="date" type="date" class="form-control"
                                        min="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" readonly>
                                @endcan
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-cash me-2"></i>
                                <select wire:model="cashID" id="cashID" class="form-select">
                                    <option value="">Выберите кассу</option>
                                    @foreach ($cashRegisters as $cash)
                                        <option value="{{ $cash->id }}"
                                            {{ $cashID == $cash->id ? 'selected' : '' }}>
                                            Касса: {{ $cash->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-card-list me-2"></i>
                                <input type="text" id="titleTemplate" class="form-control"
                                    wire:model.live="titleTemplate" placeholder="Введите название шаблона">
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <i class="bi bi-star me-2"></i>
                                <div class="dropdown">
                                    <button class="btn dropdown-toggle" type="button" id="iconDropdown"
                                        data-bs-toggle="dropdown" aria-expanded="false">
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
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success"><i
                                        class="bi bi-plus-circle"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
