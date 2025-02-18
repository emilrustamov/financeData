<div class="container">
    <div class="floating-buttons"
        style="position: fixed; bottom: 20px; right: 20px; display: flex; flex-direction: column; gap: 10px;">
        <button class="btn btn-success rounded-circle shadow" wire:click="openForm()">
            <i class="bi bi-plus-circle fs-4"></i>
        </button>
    </div>
    <x-alerts />
    <table class="table table-bordered table-hover mt-3">
        <thead class="table-light">
            <tr>
                <th>От кассы</th>
                <th>К кассе</th>
                <th>Сумма</th>
                <th>Комментарий</th>
                <th>Пользователь</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @if ($transfers->isEmpty())
                <tr>
                    <td colspan="7" class="text-center">В таблице нет данных</td>
                </tr>
            @else
                @foreach ($transfers as $transfer)
                    <tr>
                        <td>{{ $transfer->fromCash->title }} ({{ $transfer->fromCash->currency->symbol }})</td>
                        <td>{{ $transfer->toCash->title }} ({{ $transfer->toCash->currency->symbol }})</td>
                        <td>{{ number_format($transfer->amount, 2, '.', ' ') }}</td>
                        <td>{{ $transfer->note }}</td>
                        <td>{{ $transfer->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($transfer->date)->format('d.m.Y') }}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" wire:click="openForm({{ $transfer->id }})">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" wire:click="deleteTransfer({{ $transfer->id }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
    {{ $transfers->links() }}

    <div class="modal fade @if ($showForm) show @endif" tabindex="-1"
        style="@if ($showForm) display: block; @else display: none; @endif" aria-modal="true"
        role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title">{{ $transferId ? 'Редактировать трансфер' : 'Создать трансфер' }}</span>
                    <button type="button" class="btn-close" wire:click="closeForm" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="createTransfer">
                        <div class="mb-3">
                            <label for="fromCashId" class="form-label">От кассы</label>
                            <select wire:model.change="fromCashId" id="fromCashId" class="form-select">
                                <option value="">Выберите кассу</option>
                                @foreach ($cashes as $cash)
                                    <option value="{{ $cash->id }}"
                                        @if ($cash->id == $toCashId) disabled @endif>
                                        {{ $cash->title }} ({{ $cash->currency->symbol }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="toCashId" class="form-label">К кассе</label>
                            <select wire:model.change="toCashId" id="toCashId" class="form-select">
                                <option value="">Выберите кассу</option>
                                @foreach ($cashes as $cash)
                                    <option value="{{ $cash->id }}"
                                        @if ($cash->id == $fromCashId) disabled @endif>
                                        {{ $cash->title }} ({{ $cash->currency->symbol }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Сумма</label>
                            <input type="number" wire:model="amount" id="amount" class="form-control">
                        </div>

                        @php
                            $fromCash = $cashes->firstWhere('id', $fromCashId);
                            $toCash = $cashes->firstWhere('id', $toCashId);
                        @endphp
                        @if ($fromCash && $toCash && $fromCash->currency->id != $toCash->currency->id)
                            <div class="mb-3">
                                <label for="exchangeRate" class="form-label">Курс обмена</label>
                                <input type="number" step="0.0001" wire:model="exchangeRate" id="exchangeRate"
                                    class="form-control" placeholder="Укажите курс">
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="note" class="form-label">Комментарий</label>
                            <textarea wire:model="note" id="note" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="transferDate" class="form-label">Дата трансфера</label>
                            <input type="date" wire:model="transferDate" id="transferDate" class="form-control">
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
