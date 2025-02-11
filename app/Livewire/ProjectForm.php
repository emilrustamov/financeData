<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Projects;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Record;

class ProjectForm extends Component
{
    use WithPagination;

    public $title,
        $description,
        $showForm = false,
        $projectId = null;
    public $users = [];
    public $allUsers = [];


    protected $listeners = ['openForm'];

    public function mount()
    {
        $this->allUsers = User::all();
    }

    public function openForm($id = null)
    {
        $this->reset(['title', 'description', 'projectId']);
        $this->projectId = $id;

        if ($id) {
            $project = Projects::findOrFail($id);
            $this->title = $project->title;
            $this->description = $project->description;
            $this->users = $project->users ?: [];
        } else {
            $this->title = null;
            $this->description = null;
        }

        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
    }

    public function createProject()
    {
        $this->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'users'       => 'required|array|min:1',
        ]);

        $arrayOfIds = array_map('intval', $this->users);

        Projects::updateOrCreate(
            ['id' => $this->projectId],
            [
                'title'       => $this->title,
                'description' => $this->description,
                'users'       => $arrayOfIds,
            ]
        );

        session()->flash('message', $this->projectId ? 'Проект успешно обновлен.' : 'Проект успешно добавлен.');
        $this->closeForm();
    }

    public function deleteProject()
    {
        if (Record::where('project_id', $this->projectId)->exists()) {
            session()->flash('error', 'Невозможно удалить проект: к нему привязаны записи в таблице records.');
            return;
        }

        Projects::findOrFail($this->projectId)->delete();
        $this->projectId = null;
        session()->flash('message', 'Проект успешно удален.');
    }

    public function confirmDeleteProject($id)
    {
        $this->projectId = $id;
        $this->dispatch('show-delete-project-confirmation');
    }

    public function render()
    {
        $projects = Projects::orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.project-form', [
            'projects' => $projects,
        ]);
    }
}
