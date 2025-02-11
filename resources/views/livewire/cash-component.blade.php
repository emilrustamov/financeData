<div class="container">
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="floating-button">
        <button class="btn btn-success rounded-circle shadow" wire:click="openForm()">
            <i class="bi bi-plus-circle fs-4"></i>
        </button>
    </div>
    <table class="table table-bordered  table-hover">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Название</th>
                <th>Пользователи</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @if ($cashes->isEmpty())
                <tr>
                    <td colspan="4" class="text-center">Нет данных о кассах.</td>
                </tr>
            @else
                @foreach ($cashes as $cash)
                    <tr>
                        <td>{{ $cash->id }}</td>
                        <td>{{ $cash->title }}</td>
                        <td>{{ $cash->users->pluck('name')->implode(', ') }}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" wire:click="openForm({{ $cash->id }})">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" wire:click="confirmDeleteCash({{ $cash->id }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    {{ $cashes->links() }}

    <div class="modal fade @if ($showForm) show @endif" tabindex="-1"
        style="@if ($showForm) display: block; @else display: none; @endif" aria-modal="true"
        role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $cashId ? 'Редактировать кассу' : 'Добавить кассу' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeForm" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="saveCash">
                        <div class="mb-3">
                            <label for="title" class="form-label">Название кассы</label>
                            <input type="text" id="title" class="form-control" wire:model="title" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Пользователи с доступом</label>
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

                        <div class="d-flex justify-content-end">
                            <button type="submit"
                                class="btn btn-success">{{ $cashId ? 'Обновить' : 'Создать' }}</button>
                        </div>
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
