<?php

namespace App\Livewire;

use App\Models\Cash;
use App\Models\User;
use App\Models\Currency;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Record;

class CashComponent extends Component
{
    use WithPagination;

    public $title, $cashId, $currency_id, $userIds = [];
    public $showForm = false;

    protected $rules = [
        'title'       => 'required|string|max:255',
        'userIds'     => 'array',
        'currency_id' => 'required|exists:currencies,id'
    ];

    protected $listeners = ['deleteCashConfirmed'];

    public function openForm($id = null)
    {
        $this->reset();
        $this->cashId = $id;

        if ($id) {
            $cash = Cash::findOrFail($id);
            $this->title = $cash->title;
            $this->currency_id = $cash->currency_id;
            $this->userIds = $cash->users->pluck('id')->toArray();
        }

        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->reset();
        $this->showForm = false;
    }

    public function saveCash()
    {
        $this->validate();

        if ($this->cashId) {
            $cash = Cash::findOrFail($this->cashId);
            $cash->update([
                'title'       => $this->title,
                'currency_id' => $this->currency_id,
            ]);
        } else {
            $cash = Cash::create([
                'title'       => $this->title,
                'currency_id' => $this->currency_id,
            ]);
        }

        $cash->users()->sync($this->userIds);
        session()->flash('message', $this->cashId ? 'Касса обновлена.' : 'Касса создана.');
        $this->closeForm();

        $this->dispatch('cash-saved');
    }

    public function confirmDeleteCash($id)
    {
        $this->cashId = $id;
        $this->dispatch('show-delete-confirmation');
    }

    public function deleteCashConfirmed()
    {
        if (Record::where('cash_id', $this->cashId)->exists()) {
            session()->flash('error', 'Невозможно удалить кассу: к ней привязаны записи.');
            return;
        }

        Cash::findOrFail($this->cashId)->delete();
        $this->cashId = null;
        session()->flash('message', 'Касса удалена.');
        $this->dispatch('cash-deleted');
    }

    public function render()
    {
        return view('livewire.cash-component', [
            'cashes'     => Cash::paginate(10),
            'users'      => User::all(),
            'currencies' => Currency::all(),
        ]);
    }
}
