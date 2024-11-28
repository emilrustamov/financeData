<div class="container">
    <h1 class="text-xl font-bold mb-4">Управление пользователями</h1>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <button class="btn btn-primary mb-3" wire:click="openModal()">
        Добавить пользователя
    </button>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" wire:click="openModal({{ $user->id }})">Редактировать</button>
                        <button class="btn btn-sm btn-danger" wire:click="confirmDeleteUser({{ $user->id }})">Удалить</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-3">
        {{ $users->links() }}
    </div>

    <!-- Модальное окно -->
    @if ($isModalOpen)
        <div class="modal d-block">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $userId ? 'Редактировать пользователя' : 'Создать пользователя' }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="saveUser">
                            <div class="mb-3">
                                <label for="name" class="form-label">Имя</label>
                                <input type="text" id="name" wire:model.defer="name" class="form-control">
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" wire:model.defer="email" class="form-control">
                                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" id="password" wire:model.defer="password" class="form-control">
                                @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" wire:click="closeModal">Отмена</button>
                                <button type="submit" class="btn btn-primary">Сохранить</button>
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
