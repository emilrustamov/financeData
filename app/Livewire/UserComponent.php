<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class UserComponent extends Component
{
    use WithPagination;

    public $name, $email, $password, $role, $userId;
    public $isModalOpen = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'nullable|string|min:6',
    ];

    protected $listeners = ['deleteUserConfirmed'];

    public function openModal($id = null)
    {
        $this->resetFields();
        $this->userId = $id;

        if ($id) {
            $user = User::findOrFail($id);
            $this->name = $user->name;
            $this->email = $user->email;
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
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->userId = null;
    }

    public function saveUser()
    {
        $validatedData = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'password' => $this->userId ? 'nullable|string|min:6' : 'required|string|min:6',
        ]);

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update(array_filter([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password ? Hash::make($this->password) : null,
            ]));
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);
        }

        session()->flash('message', $this->userId ? 'Пользователь обновлён.' : 'Пользователь создан.');
        $this->closeModal();

        // Обновляем список пользователей
        $this->dispatch('user-saved');
    }

    public function confirmDeleteUser($id)
    {
        $this->userId = $id;
        $this->dispatch('show-delete-confirmation');
    }

    public function deleteUserConfirmed()
    {
        User::findOrFail($this->userId)->delete();
        $this->userId = null;
        session()->flash('message', 'Пользователь удалён.');

        // Обновляем список пользователей
        $this->dispatch('user-deleted');
    }

    public function render()
    {
        return view('livewire.user-component', [
            'users' => User::paginate(10), // Загружаем данные из базы
        ]);
    }
}
