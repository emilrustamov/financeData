<div class="container">
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-4">
        <button wire:click="openModal" class="btn btn-primary">Добавить кассу</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Название</th>
                <th>Валюта</th>
                <th>Пользователи</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cashes as $cash)
                <tr>
                    <td>{{ $cash->id }}</td>
                    <td>{{ $cash->title }}</td>
                    <td>{{ $cash->currency->currency ?? 'Не указано' }}</td>

                    <td>{{ $cash->users->pluck('name')->implode(', ') }}</td>
                    <td>
                        <button wire:click="openModal({{ $cash->id }})"
                            class="btn btn-warning btn-sm">Редактировать</button>
                        <button wire:click="confirmDeleteCash({{ $cash->id }})"
                            class="btn btn-danger btn-sm">Удалить</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $cashes->links() }}

    <div class="modal" tabindex="-1" role="dialog" style="display: {{ $isModalOpen ? 'block' : 'none' }}">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $cashId ? 'Редактировать' : 'Добавить' }} кассу</h5>
                    <button type="button" class="close" wire:click="closeModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="saveCash">
                        <div class="form-group">
                            <label for="title">Название кассы</label>
                            <input type="text" id="title" class="form-control" wire:model="title" required>
                        </div>
                        <div class="form-group">
                            <label for="currency">Валюта</label>
                            <select id="currency" class="form-control" wire:model="currency_id" required>
                                <option value="">Выберите валюту</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}">{{ $currency->currency }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="users">Пользователи с доступом</label>
                            <div>
                                @foreach ($users as $user)
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="user-{{ $user->id }}"
                                            value="{{ $user->id }}" wire:model="userIds">
                                        <label class="form-check-label" for="user-{{ $user->id }}">
                                            {{ $user->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">{{ $cashId ? 'Обновить' : 'Создать' }}</button>
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Отмена</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    window.addEventListener('show-delete-confirmation', () => {
        if (confirm('Вы уверены, что хотите удалить кассу?')) {
            @this.call('deleteCashConfirmed');
        }
    });
</script>

