<?php

namespace App\Livewire;

use App\Models\Record;
use App\Models\Cash;
use App\Models\CashRegister;
use App\Models\Objects;
use App\Models\ObjectCategories;
use App\Models\Projects;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class RecordForm extends Component
{
    use WithPagination;

    public $type, $desc, $amount, $date, $dateFilter, $recordId = null, $showForm = false,
        $filterType = 'all', $startDate = null, $endDate = null, $cashRegisters = [],
        $cashID = null, $cashRegFltr, $typeFilter = null, $searchTerm, $object,
        $objects, $objectCategories = [], $projects = [], $project, $category = null,
        $isCashClosedRecord = false;

    protected $listeners = [
        'openRecordModal'   => 'handleOpenRecordModal',
    ];

    protected $rules = [
        'type'     => 'required|boolean',
        'desc'     => 'nullable|string|max:255',
        'amount'   => 'required|numeric|min:0',
        'date'     => 'required|date',
        'cashID'   => 'required|exists:cashes,id',
        'object'   => 'nullable|exists:objects,id',
        'project'  => 'nullable|exists:projects,id',
        'category' => 'nullable|exists:object_categories,id',
    ];

    public function mount()
    {
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
        $this->cashRegFltr = null;
        $this->calculateTotalBalance();
    }

    public function render()
    {
        $records = $this->buildRecordQuery()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.record-form', [
            'records'      => $records,
            'dailySummary' => $this->getDailySummary(),
            'showBalance'  => $this->filterType === 'daily',
            'isCashClosed' => $this->isCashClosedForDate($this->cashRegFltr, $this->dateFilter),
            'myCashes'     => $this->myCashes,
        ]);
    }

    private function buildRecordQuery()
    {
        return Record::with(['project', 'object', 'category', 'cash'])
            ->when($this->cashRegFltr, fn($q) => $q->where('cash_id', $this->cashRegFltr))
            ->when($this->filterType === 'daily' && $this->dateFilter, fn($q) => $q->whereDate('date', $this->dateFilter))
            ->when($this->filterType === 'weekly', fn($q) => $q->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($this->filterType === 'monthly', fn($q) => $q->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]))
            ->when($this->filterType === 'custom' && $this->startDate && $this->endDate, fn($q) => $q->whereBetween('date', [$this->startDate, $this->endDate]))
            ->when(!is_null($this->typeFilter), fn($q) => $q->where('type', $this->typeFilter))
            ->when($this->searchTerm && mb_strlen($this->searchTerm) >= 3, function ($q) {
                $q->where(function ($query) {
                    $query->where('description', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('category', fn($q) => $q->where('title', 'like', '%' . $this->searchTerm . '%'))
                        ->orWhereHas('object', fn($q) => $q->where('title', 'like', '%' . $this->searchTerm . '%'));
                });
            });
    }

    public function openForm($id = null)
    {
        $this->recordId = $id;

        if ($id) {
            $record = Record::findOrFail($id);
            if ($record->transferFrom || $record->transferTo) {
                session()->flash('error', 'Запись является частью трансфера и не может быть отредактирована.');
                return;
            }
            $this->fillForm($record);
        } else {
            $this->resetForm();
        }

        $this->showForm = true;
    }

    private function fillForm(Record $record)
    {
        $this->type               = $record->type;
        $this->desc               = $record->description;
        $this->amount             = $record->amount;
        $this->object             = $record->object_id;
        $this->category           = $record->object ? $record->object->category_id : null;
        $this->project            = $record->project_id;
        $this->date               = $record->date;
        $this->cashID             = $record->cash_id;
        $this->isCashClosedRecord = $this->isCashClosedForDate($record->cash_id, $record->date);
    }

    public function closeForm()
    {
        $this->showForm = false;
    }

    private function resetForm()
    {
        $this->reset(['amount', 'object', 'project', 'category', 'isCashClosedRecord', 'type', 'desc']);
        $this->date = date('Y-m-d');
    }

    public function handleOpenRecordModal($data)
    {
        $this->fillData($data);
        $this->recordId = null;
        $this->showForm = true;
    }

    private function fillData(array $data)
    {
        $this->type     = $data['type']    ?? null;
        $this->desc     = $data['desc']    ?? null;
        $this->amount   = $data['amount']  ?? null;
        $this->date     = $data['date']    ?? null;
        $this->object   = $data['object']  ?? null;
        $this->project  = $data['project'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->cashID   = $data['cashID']  ?? null;
    }

    public function createRecord()
    {
        $this->validate();

        if ($this->isCashClosedForDate($this->cashID, $this->date)) {
            session()->flash('error', 'Касса закрыта. Изменения не могут быть сохранены.');
            return;
        }
        Record::updateOrCreate(['id' => $this->recordId], $this->buildRecordData());
        session()->flash('message', $this->recordId ? 'Запись успешно обновлена.' : 'Запись успешно добавлена.');
        $this->closeForm();
    }

    private function buildRecordData(): array
    {
        return [
            'type'         => $this->type,
            'description'  => $this->desc,
            'amount'       => $this->amount,
            'object_id'    => $this->object ?: null,
            'project_id'   => $this->project ?: null,
            'category_id'  => $this->category ?: null,
            'date'         => $this->date ?: now()->format('Y-m-d'),
            'cash_id'      => $this->cashID,
            'user_id'      => Auth::id(),
        ];
    }

    public function copyRecord($id)
    {
        $record = Record::findOrFail($id);
        $this->fillForm($record);
        $this->showForm = true;
    }

    public function deleteRecord()
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'У вас нет доступа к удалению записей.');
        }

        $record = Record::findOrFail($this->recordId);

        if ($record->transferFrom || $record->transferTo) {
            session()->flash('error', 'Запись является частью трансфера и не может быть удалена отдельно.');
            return;
        }

        if ($this->isCashClosedForDate($record->cash_id, $record->date)) {
            session()->flash('error', 'Касса закрыта. Запись не может быть удалена.');
            return;
        }

        $record->delete();
        session()->flash('message', 'Запись успешно удалена.');
    }

    public function confirmDeleteRecord($id)
    {
        $this->recordId = $id;
        $this->dispatch('show-delete-record-confirmation');
    }

    public function updatedFilterType()
    {
        if ($this->filterType === 'custom') {
            $this->startDate  = now()->startOfWeek()->format('Y-m-d');
            $this->endDate    = now()->endOfWeek()->format('Y-m-d');
            $this->dateFilter = null;
        } else {
            $this->startDate  = $this->endDate = null;
            $this->dateFilter = $this->filterType === 'daily' ? now()->format('Y-m-d') : null;
        }
    }

    public function getDailySummary()
    {
        $dailyData = Record::selectRaw('type, SUM(amount) as total')
            ->when($this->cashRegFltr, fn($q) => $q->where('cash_id', $this->cashRegFltr))
            ->when($this->dateFilter, fn($q) => $q->whereDate('date', $this->dateFilter))
            ->groupBy('type')
            ->pluck('total', 'type');

        $dailyIncome  = $dailyData->get(1, 0);
        $dailyExpense = $dailyData->get(0, 0);

        $totalData = Record::selectRaw('type, SUM(amount) as total')
            ->when($this->cashRegFltr, fn($q) => $q->where('cash_id', $this->cashRegFltr))
            ->groupBy('type')
            ->pluck('total', 'type');

        $totalIncome  = $totalData->get(1, 0);
        $totalExpense = $totalData->get(0, 0);

        return [
            'income'       => $dailyIncome,
            'expense'      => $dailyExpense,
            'balance'      => $dailyIncome - $dailyExpense,
            'totalBalance' => $totalIncome - $totalExpense,
        ];
    }

    public function calculateTotalBalance($cashId = null, $dateFilter = null)
    {
        $query  = Record::query()->when($cashId, fn($q) => $q->where('cash_id', $cashId));
        $income = (clone $query)->where('type', 1)->sum('amount');
        $expense = (clone $query)->where('type', 0)->sum('amount');

        return ['total' => $income - $expense];
    }

    public function openCashRegister()
    {
        if (!$this->cashRegFltr || !$this->dateFilter) {
            session()->flash('error', 'Не выбрана касса или дата.');
            return;
        }

        CashRegister::where('cash_id', $this->cashRegFltr)
            ->whereDate('date', $this->dateFilter)
            ->delete();

        session()->flash('message', 'Касса успешно открыта.');
        $this->dispatch('refreshComponent');
    }

    public function closeCashRegister()
    {
        $date = $this->dateFilter ?: now()->format('Y-m-d');

        if (!$this->cashRegFltr) {
            session()->flash('error', 'Выберите кассу для закрытия.');
            return;
        }

        $dailySummary = $this->getDailySummary();
        CashRegister::create([
            'date'    => $date,
            'cash_id' => $this->cashRegFltr,
            'balance' => $dailySummary['balance'] ?? 0,
        ]);

        session()->flash('message', "Касса за {$date} успешно закрыта.");
        $this->dispatch('refreshComponent');
    }

    public function getCanCloseCashRegisterProperty()
    {
        if (!$this->cashRegFltr) {
            return ['disabled' => true, 'message' => 'Выберите кассу для закрытия'];
        }

        if (!$this->dateFilter) {
            return ['disabled' => true, 'message' => 'Выберите дату для закрытия'];
        }

        if ($this->isCashClosedForDate($this->cashRegFltr, $this->dateFilter)) {
            return ['disabled' => true, 'message' => 'Касса закрыта'];
        }

        return ['disabled' => false, 'message' => 'Закрыть кассу'];
    }


    public function getFilteredObjectsProperty()
    {
        if ($this->category) {
            return $this->objects->where('category_id', $this->category);
        }
        return $this->objects;
    }

    public function isCashClosedForDate($cashId, $date)
    {
        return CashRegister::where('cash_id', $cashId)
            ->whereDate('date', $date)
            ->exists();
    }

    public function getMyCashesProperty()
    {
        if ($this->cashRegisters && count($this->cashRegisters) > 1) {
            return collect($this->cashRegisters)->map(function ($cash) {
                $income = Record::where('cash_id', $cash->id)->where('type', 1)->sum('amount');
                $expense = Record::where('cash_id', $cash->id)->where('type', 0)->sum('amount');
                $cash->balance = $income - $expense;
                return $cash;
            });
        }
        return collect([]);
    }
}
