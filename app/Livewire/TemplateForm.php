<?php

namespace App\Livewire;

use App\Models\Template;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use App\Models\Cash;
use App\Models\ObjectCategories;
use App\Models\Objects;
use App\Models\Projects;
use App\Models\CashRegister;

class TemplateForm extends Component
{
    use WithPagination;
    public
        $titleTemplate,
        $icon,
        $type,
        $desc,
        $amount,
        $date,
        $objectCategories = [],
        $objects = [],
        $object = null,
        $projects = [],
        $project,
        $category,
        $templateId = null,
        $showForm = false,
        $cashRegisters = [],
        $cashID = null,
        $availableIcons = [
            'bi-tools',
            'bi-droplet',
            'bi-cup-straw',
            'bi-pencil',
            'bi-bucket',
            'bi-cash-coin',
            'bi-person-badge',
            'bi-palette',
            'bi-shop',
            'bi-bag',
            'bi-globe',
            'bi-heart',
            'bi-briefcase',
        ];

    protected $rules = [
        'titleTemplate' => 'required|string|max:100',
        'icon' => 'required|string|max:255',
        'type' => 'required|boolean',
        'desc' => 'nullable|string|max:255',
        'amount' => 'required|numeric|min:0',
        'date' => 'required|date',
        'object' => 'required|exists:objects,id',
        'project' => 'nullable|exists:projects,id',
        'category' => 'required|exists:object_categories,id',
        'cashID' => 'required|exists:cashes,id',
    ];

    public function mount()
    {
       
    }

    public function openForm($id = null)
    {
        $this->resetExcept(['availableIcons']);
        $this->templateId = $id;
    
        if ($id) {
            $template = Template::findOrFail($id);
            $this->titleTemplate = $template->title_template;
            $this->icon = $template->icon;
            $this->type = $template->type;
            $this->desc = $template->description;
            $this->amount = $template->amount;
            $this->object = $template->object_id;
            $this->project = $template->project_id;
            $this->category = $template->category_id;
            $this->date = $template->date;
            $this->cashID = $template->cash_id;
        } else {
            $this->resetForm();
        }
    
        $this->showForm = true;
    }

  

    public function updatedSelectedCategory($categoryId)
    {
        $this->objects = Objects::where('category_id', $categoryId)->get();
    }



    public function closeForm()
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset([
            'titleTemplate',
            'icon',
            'type',
            'desc',
            'amount',
            'object',
            'project',
            'category',
            'cashID',
            'templateId'
        ]);
        $this->date = date('Y-m-d');
    }

    public function createTemplate()
    {
        $this->validate();

        $data = [
            'title_template' => $this->titleTemplate,
            'icon' => $this->icon,
            'type' => $this->type,
            'description' => $this->desc,
            'amount' => $this->amount,
            'object_id' => $this->object,
            'project_id' => $this->project,
            'category_id' => $this->category,
            'date' => $this->date,
            'cash_id' => $this->cashID,
            'user_id' => Auth::id(),
        ];

        Template::updateOrCreate(['id' => $this->templateId], $data);
        session()->flash('message', $this->templateId ? 'Шаблон успешно обновлен.' : 'Шаблон успешно добавлен.');
        $this->closeForm();
    }

    public function deleteTemplate($id)
    {
        $template = Template::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $template->delete();
        session()->flash('message', 'Шаблон успешно удален.');
    }

    public function addRecordFromTemplate($id)
    {
        $template = Template::findOrFail($id);


        $this->dispatch('openRecordModal', [
            'type' => $template->type,
            'desc' => $template->description,
            'amount' => $template->amount,
            'date' => $template->date,
            'object' => $template->object_id,
            'project' => $template->project_id,
            'category' => $template->category_id,
            'cashID' => $template->cash_id,
        ]);
    }

    public function render()
    {
        $templates = Template::where('user_id', Auth::id())->get();
        $user = Auth::user();
        $this->date = date('Y-m-d');
        if ($user->is_admin) {
            $this->cashRegisters = Cash::all();
            $this->objectCategories = ObjectCategories::all();
            $this->objects = Objects::with('category')->get();
            $this->projects = Projects::all();
        } else {
            $this->cashRegisters = $user->cashes;
            $this->objectCategories = ObjectCategories::whereJsonContains('users', $user->id)->get();
            $this->objects = Objects::with('category')->whereJsonContains('users', $user->id)->get();
            $this->projects = Projects::whereJsonContains('users', $user->id)->get();
        }

        return view('livewire.template-form', [
            'templates' => $templates,
        ]);
    }
}
