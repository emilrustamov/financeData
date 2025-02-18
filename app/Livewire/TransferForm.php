<?php

namespace App\Livewire;

use App\Models\Transfer;
use App\Models\Cash;
use App\Models\Record;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\CashRegister;

class TransferForm extends Component
{
    public $fromCashId;
    public $toCashId;
    public $amount;
    public $note;
    public $exchangeRate; // новый параметр для курса обмена
    public $transferId = null;
    public $showForm = false;
    public $transferDate;

    protected $rules = [
        'fromCashId' => 'required|exists:cashes,id|different:toCashId',
        'toCashId'   => 'required|exists:cashes,id|different:fromCashId',
        'amount'     => 'required|numeric|min:0',
        'note'       => 'nullable|string|max:255',
        // exchangeRate проверим условно в методе createTransfer
    ];

    protected $listeners = ['openForm'];

    public function openForm($id = null)
    {
        $this->resetExcept(['fromCashId', 'toCashId']);
        $this->transferId = $id;

        if ($id) {
            $transfer = Transfer::findOrFail($id);
            $this->fromCashId = $transfer->from_cash_id;
            $this->toCashId = $transfer->to_cash_id;
            $this->amount = $transfer->amount;
            $this->note = $transfer->note;
            // Если в комментарии был курс, его можно извлечь (по необходимости)
        } else {
            $this->reset(['fromCashId', 'toCashId', 'amount', 'note', 'exchangeRate']);
        }
        if (!$this->transferDate) {
            $this->transferDate = now()->format('Y-m-d');
        }

        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
    }

    public function createTransfer()
    {
        $rules = $this->rules;

        // Получаем кассы с валютами
        $fromCash = Cash::with('currency')->find($this->fromCashId);
        $toCash   = Cash::with('currency')->find($this->toCashId);

        // Если валюты отличаются – курс обязателен
        if ($fromCash && $toCash && $fromCash->currency->id != $toCash->currency->id) {
            $rules['exchangeRate'] = 'required|numeric|min:0.0001';
        }
        $this->validate($rules);

        // Если одна из касс закрыта
        if ($this->isCashClosed($this->fromCashId) || $this->isCashClosed($this->toCashId)) {
            session()->flash('error', 'Трансфер невозможен. Одна из касс закрыта.');
            return;
        }

        $data = [
            'from_cash_id' => $this->fromCashId,
            'to_cash_id'   => $this->toCashId,
            'amount'       => $this->amount, // исходная сумма (в валюте исходной кассы)
            'user_id'      => Auth::id(),
        ];

        // Если валюты отличаются, пересчитываем сумму для целевой кассы
        if ($fromCash && $toCash && $fromCash->currency->id != $toCash->currency->id) {
            $toAmount = $this->amount * $this->exchangeRate;
            // Добавляем курс в комментарий
            $data['note'] = ($this->note ?: "Трансфер с кассы " . $fromCash->title . " на кассу " . $toCash->title)
                . " | Курс: " . $this->exchangeRate;
        } else {
            $data['note'] = $this->note ?: "Трансфер с кассы " . $fromCash->title . " на кассу " . $toCash->title;
        }

        // При обновлении трансфера удаляем старые записи (если есть) и создаём новые с перерасчётом
        if ($this->transferId) {
            $transfer = Transfer::findOrFail($this->transferId);

            if ($transfer->from_record_id) {
                Record::destroy($transfer->from_record_id);
            }
            if ($transfer->to_record_id) {
                Record::destroy($transfer->to_record_id);
            }

            // Создаем запись для исходной кассы (снимается исходная сумма)
            $fromRecord = Record::create([
                'type'        => 0,
                'description' => $data['note'],
                'amount'      => $this->amount,
                'date'        => $this->transferDate,
                'cash_id'     => $this->fromCashId,
                'user_id'     => Auth::id(),
            ]);

            // Создаем запись для целевой кассы
            $toRecord = Record::create([
                'type'        => 1,
                'description' => $data['note'],
                'amount'      => isset($toAmount) ? $toAmount : $this->amount,
                'date'        => $this->transferDate,
                'cash_id'     => $this->toCashId,
                'user_id'     => Auth::id(),
            ]);

            $data['from_record_id'] = $fromRecord->id;
            $data['to_record_id']   = $toRecord->id;
            $transfer->update($data);
        } else {
            // Создаем новые записи для трансфера
            $fromRecord = Record::create([
                'type'        => 0,
                'description' => $data['note'],
                'amount'      => $this->amount,
                'date'        => $this->transferDate,
                'cash_id'     => $this->fromCashId,
                'user_id'     => Auth::id(),
            ]);

            $toRecord = Record::create([
                'type'        => 1,
                'description' => $data['note'],
                'amount'      => isset($toAmount) ? $toAmount : $this->amount,
                'date'        => $this->transferDate,
                'cash_id'     => $this->toCashId,
                'user_id'     => Auth::id(),
            ]);

            $data['from_record_id'] = $fromRecord->id;
            $data['to_record_id']   = $toRecord->id;
            Transfer::create($data);
        }

        session()->flash('message', $this->transferId ? 'Трансфер успешно обновлен.' : 'Трансфер успешно создан.');
        $this->closeForm();
    }

    public function deleteTransfer($id)
    {
        $transfer = Transfer::findOrFail($id);

        if ($this->isCashClosed($transfer->from_cash_id) || $this->isCashClosed($transfer->to_cash_id)) {
            session()->flash('error', 'Трансфер невозможно удалить. Одна из касс закрыта.');
            return;
        }

        Record::destroy($transfer->from_record_id);
        Record::destroy($transfer->to_record_id);
        $transfer->delete();

        session()->flash('message', 'Трансфер успешно удален.');
    }

    public function isCashClosed($cashId)
    {
        return \App\Models\CashRegister::where('cash_id', $cashId)
            ->whereDate('date', $this->transferDate)
            ->exists();
    }

    public function render()
    {
        $transfers = Transfer::with(['fromCash', 'toCash', 'user'])->paginate(20);
        // Загружаем кассы вместе с валютой
        $cashes = Cash::with('currency')->get();

        return view('livewire.transfer-form', [
            'transfers' => $transfers,
            'cashes'    => $cashes,
        ]);
    }
}
