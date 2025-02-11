<div class="container">
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="floating-buttons"
        style="position: fixed; bottom: 20px; right: 20px; display: flex; flex-direction: column; gap: 10px;">
        <button class="btn btn-success rounded-circle shadow" wire:click="openForm()">
            <i class="bi bi-plus-circle fs-4"></i>
        </button>
    </div>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @if ($users->isEmpty())
                <tr>
                    <td colspan="5" class="text-center">Нет данных о пользователях.</td>
                </tr>
            @else
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->is_admin ? 'Администратор' : 'Пользователь' }}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" wire:click="openForm({{ $user->id }})">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" wire:click="confirmDeleteUser({{ $user->id }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>


    <div class="mt-3">
        {{ $users->links() }}
    </div>

    <!-- Модальное окно -->
    @if ($showForm)
        <div class="modal d-block">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $userId ? 'Редактировать пользователя' : 'Создать пользователя' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeForm"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="saveUser">
                            <h6 class="form-label">Общая информация</h6>
                            <div class="mb-3">
                                <label for="name" class="form-label">Имя</label>
                                <input type="text" id="name" wire:model.defer="name" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" wire:model.defer="email" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" id="password" wire:model.defer="password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <h6 class="form-label">Права пользователя</h6>
                                <label class="form-label">Роль</label>
                                <div>
                                    <label class="me-3">
                                        <input type="radio" wire:model.defer="is_admin" value="0"> Пользователь
                                    </label>
                                    <label>
                                        <input type="radio" wire:model.defer="is_admin" value="1"> Администратор
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Права доступа</label>
                                @foreach ($allPermissions as $permission)
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" wire:model="userPermissions"
                                            value="{{ $permission->name }}" id="perm_{{ $permission->id }}">
                                        <label class="form-check-label" for="perm_{{ $permission->id }}">
                                            @switch($permission->name)
                                                @case('edit transactions')
                                                    Может редактировать записи
                                                @break
                                                @case('edit date')
                                                Может ставить прошедщие даты
                                            @break
                                                @case('view transfers')
                                                    Может управлять трансферами
                                                @break

                                                @case('view analytics')
                                                    Может смотреть аналитику
                                                @break

                                                @case('delete transactions')
                                                    Может удалять записи
                                                @break

                                                @case('view projects')
                                                    Может управлять проектами
                                                @break

                                                @case('view objects')
                                                    Может управлять контрагентами
                                                @break

                                                @default
                                                    {{ $permission->name }}
                                            @endswitch
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mb-3">
                                <label class="form-check-label" for="is_active">Активность</label>
                                <div class="form-check">
                                    <input type="checkbox" id="is_active" wire:model.defer="is_active" class="form-check-input">
                                    <label class="form-check-label" for="is_active">Активен</label>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">

                                <button type="submit" class="btn btn-success">Сохранить</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        window.addEventListener('show-delete-confirmation', () => {
            if (confirm('Вы уверены, что хотите удалить пользователя?')) {
                @this.call('deleteUserConfirmed');
            }
        });
    </script>
</div>
