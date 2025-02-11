<div class="mt-3 container">
    <div class="floating-button">
        <button class="btn btn-success rounded-circle shadow" wire:click="openForm()">
            <i class="bi bi-plus-circle fs-4"></i>
        </button>
    </div>
    <div class="floating-button2">
        <button class="btn btn-primary rounded-circle shadow" wire:click="openCategoryModal()">
            <i class="bi bi-people fs-4"></i>
        </button>
    </div>
    <x-alerts />
    <div class=" mt-3">
        <h5 class="mb-0">Контрагенты</h5>
        <table class="table table-bordered  table-hover">
            <thead class="table-light">
                <tr>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Категория</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @if ($objects->isEmpty())
                    <tr>
                        <td colspan="4" class="text-center">Нет данных об объектах.</td>
                    </tr>
                @else
                    @foreach ($objects as $object)
                        <tr>
                            <td>{{ $object->title }}</td>
                            <td>{{ $object->description ?: 'Нет описания' }}</td>
                            <td>{{ $object->category->title }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" wire:click="openForm({{ $object->id }})">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-danger"
                                    wire:click="confirmDeleteObject({{ $object->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <div class="mt-4">
            {{ $objects->links() }}
        </div>
    </div>

    <div class=" mt-3">

        <h5 class="mb-0">Категории контрагентов</h5>

        <table class="table table-bordered  table-hover">
            <thead class="table-light">
                <tr>
                    <th>Название</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @if ($categories->isEmpty())
                    <tr>
                        <td colspan="2" class="text-center">Нет данных о категориях.</td>
                    </tr>
                @else
                    @foreach ($categories as $category)
                        <tr>
                            <td>{{ $category->title }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning"
                                    wire:click="openCategoryModal({{ $category->id }})">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-danger"
                                    wire:click="confirmDeleteCategory({{ $category->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <!-- Модальное окно для объектов -->
    <div class="modal fade @if ($showForm) show @endif" tabindex="-1"
        style="@if ($showForm) display: block; @else display: none; @endif" aria-modal="true"
        role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">

                    <span class="modal-title">
                        {{ $objectId ? 'Редактировать объект' : 'Добавить объект' }}
                    </span>

                    <button type="button" class="btn-close" wire:click="closeForm" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="createObject">
                        <div class="mb-3">
                            <label for="title" class="form-label">Название</label>
                            <input type="text" id="title" class="form-control" wire:model="title">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea id="description" class="form-control" wire:model="description"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Категория</label>
                            <div class="input-group">
                                <select id="category_id" class="form-select" wire:model="category_id">
                                    <option value="" selected>Выберите категорию</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Пользователи, имеющие доступ</label>
                            @foreach ($allUsers as $user)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" wire:model="users"
                                        value="{{ $user->id }}" id="user_{{ $user->id }}">
                                    <label class="form-check-label" for="user_{{ $user->id }}">
                                        {{ $user->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для категорий -->
    <div class="modal fade @if ($isCategoryModalOpen) show @endif" tabindex="-1"
        style="@if ($isCategoryModalOpen) display: block; @else display: none; @endif" aria-modal="true"
        role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">

                    <span class="modal-title">
                        {{ $categoryId ? 'Редактировать категорию' : 'Добавить категорию' }}
                    </span>

                    <button type="button" class="btn-close" wire:click="closeCategoryModal"
                        aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="createCategory">
                        <div class="mb-3">
                            <label for="category_title" class="form-label">Название категории</label>
                            <input type="text" id="category_title" class="form-control"
                                wire:model="category_title">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Пользователи, имеющие доступ</label>
                            @foreach ($allUsers as $user)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" wire:model="users"
                                        value="{{ $user->id }}" id="user_{{ $user->id }}">
                                    <label class="form-check-label" for="user_{{ $user->id }}">
                                        {{ $user->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('show-delete-object-confirmation', () => {
        if (confirm('Вы уверены, что хотите удалить объект?')) {
            @this.call('deleteObject'); // Вызываем метод Livewire для удаления объекта
        }
    });

    window.addEventListener('show-delete-category-confirmation', () => {
        if (confirm('Вы уверены, что хотите удалить категорию?')) {
            @this.call('deleteCategory'); // Вызываем метод Livewire для удаления категории
        }
    });

    window.addEventListener('category-created', () => {
        @this.call('refreshCategories');
    });
</script>
