<div class="mt-3 container">
    <div class="floating-buttons"
        style="position: fixed; bottom: 20px; right: 20px; display: flex; flex-direction: column; gap: 10px;">
        <button class="btn btn-success rounded-circle shadow" wire:click="openForm()">
            <i class="bi bi-plus-circle fs-4"></i>
        </button>
    </div>
    <x-alerts />

    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Название</th>
                <th>Описание</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $project)
                <tr>
                    <td>{{ $project->title }}</td>
                    <td>{{ $project->description ?: 'Нет описания' }}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" wire:click="openForm({{ $project->id }})">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" wire:click="confirmDeleteProject({{ $project->id }})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


    <!-- Модальное окно для объектов -->
    <div class="modal fade @if ($showForm) show @endif" tabindex="-1"
        style="@if ($showForm) display: block; @else display: none; @endif" aria-modal="true"
        role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">

                    <span class="modal-title">
                        {{ $projectId ? 'Редактировать проект' : 'Добавить проект' }}
                    </span>

                    <button type="button" class="btn-close" wire:click="closeForm" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="createProject    ">
                        <div class="mb-3">
                            <label for="title" class="form-label">Название</label>
                            <input type="text" id="title" class="form-control" wire:model="title">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea id="description" class="form-control" wire:model="description"></textarea>
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
    window.addEventListener('show-delete-project-confirmation', () => {
        if (confirm('Вы уверены, что хотите удалить объект?')) {
            @this.call('deleteProject'); // Вызываем метод Livewire для удаления объекта
        }
    });
</script>
