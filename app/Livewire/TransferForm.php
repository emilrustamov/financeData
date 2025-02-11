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
    public $transferId = null;
    public $showForm = false;

    protected $rules = [
        'fromCashId' => 'required|exists:cashes,id|different:toCashId',
        'toCashId' => 'required|exists:cashes,id|different:fromCashId',
        'amount' => 'required|numeric|min:0',
        'note' => 'nullable|string|max:255',
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
        } else {
            $this->reset(['fromCashId', 'toCashId', 'amount', 'note']);
        }

        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
    }

    public function createTransfer()
    {
        $this->validate();

        if ($this->isCashClosed($this->fromCashId) || $this->isCashClosed($this->toCashId)) {
            session()->flash('error', 'Трансфер невозможен. Одна из касс закрыта.');
            return;
        }

        $data = [
            'from_cash_id' => $this->fromCashId,
            'to_cash_id'   => $this->toCashId,
            'amount'       => $this->amount,
            'user_id'      => Auth::id(),
            'note'         => $this->note ?: "Трансфер с кассы " . Cash::find($this->fromCashId)->title . " на кассу " . Cash::find($this->toCashId)->title,
        ];

        // Если обновляем существующий трансфер
        if ($this->transferId) {
            $transfer = Transfer::findOrFail($this->transferId);

            // Удаляем старые записи, если они существуют
            if ($transfer->from_record_id) {
                Record::destroy($transfer->from_record_id);
            }
            if ($transfer->to_record_id) {
                Record::destroy($transfer->to_record_id);
            }

            // Создаем новые записи
            $fromRecord = Record::create([
                'type'        => 0,
                'description' => $data['note'],
                'amount'      => $this->amount,
                'date'        => now()->format('Y-m-d'),
                'cash_id'     => $this->fromCashId,
                'user_id'     => Auth::id(),
            ]);

            $toRecord = Record::create([
                'type'        => 1,
                'description' => $data['note'],
                'amount'      => $this->amount,
                'date'        => now()->format('Y-m-d'),
                'cash_id'     => $this->toCashId,
                'user_id'     => Auth::id(),
            ]);

            // Обновляем трансфер с идентификаторами записей
            $data['from_record_id'] = $fromRecord->id;
            $data['to_record_id']   = $toRecord->id;
            $transfer->update($data);
        } else {
            // Если создаем новый трансфер:
            // Сначала создаем записи для расхода и прихода
            $fromRecord = Record::create([
                'type'        => 0,
                'description' => $data['note'],
                'amount'      => $this->amount,
                'date'        => now()->format('Y-m-d'),
                'cash_id'     => $this->fromCashId,
                'user_id'     => Auth::id(),
            ]);

            $toRecord = Record::create([
                'type'        => 1,
                'description' => $data['note'],
                'amount'      => $this->amount,
                'date'        => now()->format('Y-m-d'),
                'cash_id'     => $this->toCashId,
                'user_id'     => Auth::id(),
            ]);

            // Передаем идентификаторы записей в массив данных и создаем трансфер
            $data['from_record_id'] = $fromRecord->id;
            $data['to_record_id']   = $toRecord->id;
            $transfer = Transfer::create($data);
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
        return CashRegister::where('cash_id', $cashId)
            ->whereDate('date', now()->format('Y-m-d'))
            ->exists();
    }

    public function render()
    {
        $transfers = Transfer::with(['fromCash', 'toCash', 'user'])->paginate(20);
        $cashes = Cash::all();

        return view('livewire.transfer-form', [
            'transfers' => $transfers,
            'cashes' => $cashes,
        ]);
    }
}
