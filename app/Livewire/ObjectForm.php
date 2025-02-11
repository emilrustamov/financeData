<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Objects;
use App\Models\ObjectCategories;
use App\Models\User;
use App\Models\Record;
use Livewire\WithPagination;

class ObjectForm extends Component
{
    use WithPagination;

    public $title;
    public $description;
    public $category_id;
    public $categories;
    public $objectId = null;
    public $showForm = false;
    public $isCategoryModalOpen = false;
    public $categoryId = null;
    public $category_title;
    public $allUsers = [];
    public $users = [];

    public function mount()
    {
        $this->categories = ObjectCategories::all();
        $this->allUsers = User::all();
    }

    public function openForm($id = null)
    {
        $this->resetExcept(['categories', 'allUsers']);
        $this->objectId = $id;

        if ($id) {
            $object = Objects::findOrFail($id);
            $this->title = $object->title;
            $this->description = $object->description;
            $this->category_id = $object->category_id;
            $this->users = $object->users ?: [];
        } else {
            $this->title = null;
            $this->description = null;
            $this->category_id = null;
            $this->users = [];
        }

        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
    }

    public function openCategoryModal($id = null)
    {
        $this->resetExcept(['categories', 'allUsers']);
        $this->categoryId = $id;

        if ($id) {
            $category = ObjectCategories::findOrFail($id);
            $this->category_title = $category->title;
            $this->users = $category->users ?: [];
        } else {
            $this->category_title = null;
            $this->users = [];
        }

        $this->isCategoryModalOpen = true;
    }

    public function closeCategoryModal()
    {
        $this->isCategoryModalOpen = false;
    }

    public function createObject()
    {
        $this->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:object_categories,id',
            'users'       => 'required|array|min:1',
        ]);

        $arrayOfIds = array_map('intval', $this->users);

        Objects::updateOrCreate(
            ['id' => $this->objectId],
            [
                'title'       => $this->title,
                'description' => $this->description,
                'category_id' => $this->category_id,
                'users'       => $arrayOfIds,
            ]
        );

        $this->resetExcept(['categories', 'allUsers']);
        session()->flash('message', $this->objectId ? 'Объект успешно обновлен.' : 'Объект успешно добавлен.');
        $this->closeForm();
    }

    public function createCategory()
    {
        $this->validate([
            'category_title' => 'required|string|max:255',
            'users'          => 'required|array|min:1',
        ]);

        $arrayOfIds = array_map('intval', $this->users);

        ObjectCategories::updateOrCreate(
            ['id' => $this->categoryId],
            [
                'title' => $this->category_title,
                'users' => $arrayOfIds,
            ]
        );

        $this->categories = ObjectCategories::all();
        session()->flash('message', $this->categoryId ? 'Категория успешно обновлена.' : 'Категория успешно добавлена.');
        $this->closeCategoryModal();
    }

    public function deleteObject()
    {
        // Проверяем, есть ли связанные записи
        if (Record::where('object_id', $this->objectId)->exists()) {
            session()->flash('error', 'Невозможно удалить объект: к нему привязаны транзакции.');
            return;
        }

        Objects::findOrFail($this->objectId)->delete();
        $this->objectId = null;
        session()->flash('message', 'Контрагент успешно удален.');
    }

    public function confirmDeleteObject($id)
    {
        $this->objectId = $id;
        $this->dispatch('show-delete-object-confirmation');
    }

    public function deleteCategory()
    {
        if (Record::where('category_id', $this->categoryId)->exists()) {
            session()->flash('error', 'Невозможно удалить категорию: к ней привязаны транзакции.');
            return;
        }

        ObjectCategories::findOrFail($this->categoryId)->delete();
        $this->categoryId = null;
        $this->categories = ObjectCategories::all();
        session()->flash('message', 'Категория успешно удалена.');
    }

    public function confirmDeleteCategory($id)
    {
        $this->categoryId = $id;
        $this->dispatch('show-delete-category-confirmation');
    }

    public function render()
    {
        $objects = Objects::orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.object-form', [
            'objects' => $objects,
        ]);
    }
}
