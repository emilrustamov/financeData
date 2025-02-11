<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Record;
use Spatie\Permission\Models\Permission;

class UserComponent extends Component
{
    use WithPagination;

    public $name, $email, $password, $is_admin = 0, $is_active = true, $userId;
    public $showForm = false;
    public $allPermissions = [], $userPermissions = [];

    protected $rules = [
        'name'      => 'required|string|max:255',
        'email'     => 'required|email|unique:users,email',
        'password'  => 'nullable|string|min:6',
        'is_admin'  => 'required|boolean',
    ];

    protected $listeners = ['deleteUserConfirmed'];

    public function mount()
    {
        $this->allPermissions = Permission::all();
    }

    public function openForm($id = null)
    {
        $this->resetFields();
        $this->userId = $id;

        if ($id) {
            $user = User::findOrFail($id);
            $this->name      = $user->name;
            $this->email     = $user->email;
            $this->is_admin  = $user->is_admin;
            $this->userPermissions = $user->getPermissionNames()->toArray();
        }
        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->resetFields();
        $this->showForm = false;
    }

    public function resetFields()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->is_admin = 0;
        $this->userId = null;
        $this->userPermissions = [];
    }

    public function saveUser()
    {
        $validatedData = $this->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $this->userId,
            'password'  => $this->userId ? 'nullable|string|min:6' : 'required|string|min:6',
            'is_admin'  => 'required|boolean',
            // Убираем валидацию is_active, чтобы принять значение из радио
        ]);

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $data = [
                'name'      => $this->name,
                'email'     => $this->email,
                'is_admin'  => $this->is_admin,
                'is_active' => $this->is_active,
            ];
            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }
            $user->update($data);
        } else {
            $user = User::create([
                'name'      => $this->name,
                'email'     => $this->email,
                'password'  => Hash::make($this->password),
                'is_admin'  => $this->is_admin,
                'is_active' => $this->is_active,
            ]);
        }

        $user->syncPermissions($this->userPermissions);

        if (
            $this->userId
            && auth()->id() === $this->userId
            && (!$user->is_active || $this->password)
        ) {
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();
            return redirect('/login')
                ->with('message', 'Ваш аккаунт был деактивирован или пароль изменён. Пожалуйста, авторизуйтесь заново.');
        }

        session()->flash('message', $this->userId ? 'Пользователь обновлён.' : 'Пользователь создан.');
        $this->closeForm();
        $this->dispatch('user-saved');
    }

    public function confirmDeleteUser($id)
    {
        $this->userId = $id;
        $this->dispatch('show-delete-confirmation');
    }

    public function deleteUserConfirmed()
    {
        if (Record::where('user_id', $this->userId)->exists()) {
            session()->flash('error', 'Невозможно удалить пользователя: к нему привязаны записи.');
            return;
        }

        User::findOrFail($this->userId)->delete();
        $this->userId = null;
        session()->flash('message', 'Пользователь удалён.');

        $this->dispatch('user-deleted');
    }

    public function render()
    {
        return view('livewire.user-component', [
            'users' => User::paginate(10),
        ]);
    }
}
