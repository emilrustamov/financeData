<?php


namespace App\Livewire;

use App\Models\Cash;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class CashComponent extends Component
{
    use WithPagination;

    public $title, $cashId, $userIds = [];
    public $isModalOpen = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'userIds' => 'array',
    ];

    protected $listeners = ['deleteCashConfirmed'];

    public function openModal($id = null)
    {
        $this->resetFields();
        $this->cashId = $id;

        if ($id) {
            $cash = Cash::findOrFail($id);
            $this->title = $cash->title;
            $this->userIds = $cash->users->pluck('id')->toArray();
        }

        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->resetFields();
        $this->isModalOpen = false;
    }

    public function resetFields()
    {
        $this->title = '';
        $this->cashId = null;
        $this->userIds = [];
    }

    public function saveCash()
    {
        $validatedData = $this->validate([
            'title' => 'required|string|max:255',
            'userIds' => 'array',
        ]);

        if ($this->cashId) {
            $cash = Cash::findOrFail($this->cashId);
            $cash->update(['title' => $this->title]);
        } else {
            $cash = Cash::create(['title' => $this->title]);
        }

        $cash->users()->sync($this->userIds);

        session()->flash('message', $this->cashId ? 'Касса обновлена.' : 'Касса создана.');
        $this->closeModal();

        $this->dispatch('cash-saved');
    }

    public function confirmDeleteCash($id)
    {
        $this->cashId = $id;
        $this->dispatch('show-delete-confirmation');
    }

    public function deleteCashConfirmed()
    {
        Cash::findOrFail($this->cashId)->delete();
        $this->cashId = null;
        session()->flash('message', 'Касса удалена.');
        $this->dispatch('cash-deleted');
    }

    public function render()
    {
        return view('livewire.cash-component', [
            'cashes' => Cash::paginate(10),
            'users' => User::all(),
        ]);
    }
}
