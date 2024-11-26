<?php

namespace App\Livewire;

use App\Models\Record;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\ExchangeRate;
use Livewire\WithPagination;
use App\Models\Template;
use Illuminate\Support\Facades\File;
use App\Models\CashRegister;
use Carbon\Carbon;



class RecordForm extends Component
{
    use WithPagination;
    public $articleType, $articleDescription, $amount, $currency, $date, $exchangeRate, $link;
    public $isTemplate = false;
    public $dateFilter;
    public $titleTemplate, $icon;
    public $templates = [];
    public $recordId = null;
    public $isModalOpen = false;
    public $object;
    public $filterType = 'daily'; // Тип фильтра: daily, weekly, monthly, custom
    public $startDate = null;    // Для пользовательского диапазона
    public $endDate = null;      // Для пользовательского диапазона

    public $availableIcons = [
        'bi-fuel-pump',
        'bi-tools',
        'bi-droplet-half',
        'bi-droplet',
        'bi-cup-straw',
        'bi-cart',
        'bi-pencil',
        'bi-bucket',
        'bi-cash-coin',
        'bi-person-badge',
        'bi-person-workspace',
    ]; // сделать пустым, если используется метод getAvailableIcons
    public $isCashClosed = false;
    public $defaultExchangeRates = [];
    public $suggestions = [];
    protected $rules = [

        'articleType' => 'required|in:Приход,Расход',
        'articleDescription' => 'nullable|string|max:255',
        'amount' => 'required|numeric|min:0',
        'currency' => 'required|in:Манат,Доллар,Рубль,Юань',
        'date' => 'required|date',
        'exchangeRate' => 'nullable|numeric|min:0',
        'link' => 'nullable|string',
        'titleTemplate' => 'required_if:isTemplate,true|string|max:100',
        'icon' => 'required_if:isTemplate,true|string|max:255',
    ];


    public function openModal($id = null, $isTemplate = false)
    {
        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter']);
        $this->recordId = $id;
        $this->isTemplate = $isTemplate;

        if ($id) {
            if ($isTemplate) {

                $template = Template::findOrFail($id);

                $this->titleTemplate = $template->title_template;
                $this->icon = $template->icon;
                $this->articleType = $template->ArticleType;
                $this->articleDescription = $template->ArticleDescription;
                $this->amount = $template->Amount;
                $this->currency = $template->Currency;
                $this->date = $template->Date;
                $this->exchangeRate = $template->ExchangeRate;
                $this->link = $template->Link;
                $this->object = $template->Object;
            } else {

                $record = Record::findOrFail($id);

                $this->articleType = $record->ArticleType;
                $this->articleDescription = $record->ArticleDescription;
                $this->amount = $record->Amount;
                $this->currency = $record->Currency;
                $this->date = $record->Date;
                $this->exchangeRate = $record->ExchangeRate;
                $this->link = $record->Link;
                $this->object = $record->Object;
            }
        }

        $this->isModalOpen = true;
    }


    public function updatedCurrency($value)
    {

        if (isset($this->defaultExchangeRates[$value])) {
            $this->exchangeRate = $this->defaultExchangeRates[$value];
        } else {
            $this->exchangeRate = null;
        }
    }


    public function closeModal()
    {
        $this->isModalOpen = false;
    }


    public function updatedObject($value)
    {

        $this->suggestions = Record::where('Object', 'like', "%$value%")
            ->distinct()
            ->take(10)
            ->pluck('Object')
            ->toArray();
    }


    public function submit()
    {
        $rules = [
            'articleType' => 'required|in:Приход,Расход',
            'articleDescription' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:Манат,Доллар,Рубль,Юань',
            'date' => 'required|date',
            'exchangeRate' => 'nullable|numeric|min:0',
            'link' => 'nullable|string',
        ];

        if ($this->isTemplate) {
            $rules['titleTemplate'] = 'required|string|max:100';
            $rules['icon'] = 'required|string|max:255';
        }

        $this->validate($rules);

        $date = $this->date ?: now()->format('Y-m-d');

        // Проверка: закрыта ли касса за выбранную дату
        if (CashRegister::whereDate('Date', $date)->exists()) {
            session()->flash('error', 'Касса за этот день уже закрыта. Невозможно выполнить операцию.');
            return;
        }

        $model = $this->isTemplate ? Template::class : Record::class;

        $data = [
            'ArticleType' => $this->articleType,
            'ArticleDescription' => $this->articleDescription,
            'Amount' => $this->amount,
            'Currency' => $this->currency,
            'Date' => $this->date,
            'ExchangeRate' => $this->exchangeRate ?: null,
            'Link' => $this->link,
            'Object' => $this->object,
        ];

        if ($this->isTemplate) {
            $data['title_template'] = $this->titleTemplate;
            $data['icon'] = $this->icon;

            // Обновляем или создаем запись в шаблонах
            Template::updateOrCreate(
                ['id' => $this->recordId],
                $data
            );
        } else {
            // Обновляем или создаем запись в таблице Record
            $record = Record::updateOrCreate(
                ['id' => $this->recordId],
                $data
            );
        }

        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter']);
        session()->flash('message', $this->recordId ? 'Запись успешно обновлена.' : 'Запись успешно добавлена.');
        $this->closeModal();
    }


    public function updatedFilterType()
    {
        if ($this->filterType === 'custom') {
            $this->startDate = now()->startOfWeek()->format('Y-m-d');
            $this->endDate = now()->endOfWeek()->format('Y-m-d');
        } else {
            $this->startDate = null;
            $this->endDate = null;
            $this->dateFilter = $this->filterType === 'daily' ? now()->format('Y-m-d') : null;
        }

        $this->getDailySummary(); // Перезапускаем расчет
    }





    public function copyRecord($id)
    {
        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter']);

        $record = Record::findOrFail($id);
        $this->articleType = $record->ArticleType;
        $this->articleDescription = $record->ArticleDescription;
        $this->amount = $record->Amount;
        $this->currency = $record->Currency;
        $this->date = $record->Date;
        $this->exchangeRate = $record->ExchangeRate;
        $this->link = $record->Link;
        $this->object = $record->Object;
        $this->recordId = null;
        $this->isModalOpen = true;
    }


    public function deleteRecord($id)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'У вас нет доступа к удалению записей.');
        }
        Record::findOrFail($id)->delete();
        session()->flash('message', 'Запись успешно удалена.');
    }



    public function applyTemplate($id)
    {
        $template = Template::findOrFail($id);

        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter']);
        $this->isTemplate = false;
        $this->articleType = $template->ArticleType;
        $this->articleDescription = $template->ArticleDescription;
        $this->amount = $template->Amount;
        $this->currency = $template->Currency;
        $this->date = $template->Date;
        $this->exchangeRate = $template->ExchangeRate;
        $this->link = $template->Link;
        $this->object = $template->Object;
        $this->isModalOpen = true;
    }


    public function getDailySummary()
    {
        $incomeQuery = Record::query();
        $expenseQuery = Record::query();
        
        if ($this->filterType === 'daily' && $this->dateFilter) {
            $incomeQuery->whereDate('Date', $this->dateFilter);
            $expenseQuery->whereDate('Date', $this->dateFilter);
        } elseif ($this->filterType === 'weekly') {
            $incomeQuery->whereBetween('Date', [now()->startOfWeek(), now()->endOfWeek()]);
            $expenseQuery->whereBetween('Date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->filterType === 'monthly') {
            $incomeQuery->whereBetween('Date', [now()->startOfMonth(), now()->endOfMonth()]);
            $expenseQuery->whereBetween('Date', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($this->filterType === 'custom' && $this->startDate && $this->endDate) {
            $incomeQuery->whereBetween('Date', [$this->startDate, $this->endDate]);
            $expenseQuery->whereBetween('Date', [$this->startDate, $this->endDate]);
        }
    
        // Считаем доходы и расходы
        $income = $incomeQuery->where('ArticleType', 'Приход')->sum('Amount');
        $expense = $expenseQuery->where('ArticleType', 'Расход')->sum('Amount');
        
        // Для фильтра "по дням" считаем баланс
        $balance = $this->filterType === 'daily' 
            ? CashRegister::whereDate('Date', $this->dateFilter)->value('balance') ?? 'Не закрыта'
            : null;
    
        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
        ];
    }
    


    public function closeCashRegister()
    {
        // Проверяем, выбрана ли дата, если нет — используем текущую дату
        $date = $this->dateFilter ?: now()->format('Y-m-d');

        // Проверяем, если дата в будущем — запрет закрытия
        if (Carbon::parse($date)->isFuture()) {
            session()->flash('error', 'Нельзя закрыть кассу за будущий день.');
            return;
        }

        // Проверяем, закрыта ли касса за выбранную дату
        if (CashRegister::whereDate('Date', $date)->exists()) {
            session()->flash('error', 'Касса уже закрыта за выбранный день.');
            return;
        }

        // Получаем начальный баланс из последней закрытой даты
        $previousBalance = CashRegister::whereDate('Date', '<', $date)
            ->orderBy('Date', 'desc')
            ->value('balance') ?: 0;

        // Считаем приход и расход за выбранную дату
        $income = Record::whereDate('Date', $date)->where('ArticleType', 'Приход')->sum('Amount');
        $expense = Record::whereDate('Date', $date)->where('ArticleType', 'Расход')->sum('Amount');

        // Рассчитываем итоговый баланс
        $currentBalance = $previousBalance + $income - $expense;

        // Фиксируем баланс за выбранную дату
        CashRegister::create([
            'Date' => $date,
            'balance' => $currentBalance,
        ]);

        session()->flash('message', "Касса за {$date} успешно закрыта.");
    }



    //раскоментируйте если нужны все иконки из bootstrap-icons
    // public function getAvailableIcons() 
    // {
    //     $icons = [];
    //     $iconFilePath = base_path('node_modules/bootstrap-icons/font/bootstrap-icons.css');

    //     if (File::exists($iconFilePath)) {
    //         $content = File::get($iconFilePath);
    //         preg_match_all('/\.bi-[a-z0-9\-]+/i', $content, $matches);

    //         if (!empty($matches[0])) {
    //             $icons = array_map(fn($icon) => ltrim($icon, '.'), $matches[0]);
    //         }
    //     }

    //     return $icons;
    // }


    public function deleteTemplate($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();

        $this->templates = Template::all(); // Обновляем список шаблонов
        session()->flash('message', 'Шаблон успешно удалён.');
    }


    public function mount()
    {
        $this->templates = Template::all();
        $this->defaultExchangeRates = ExchangeRate::pluck('rate', 'currency')->toArray();
        $this->availableIcons;
    }


    public function render()
    {
        $dailySummary = $this->getDailySummary();

        $records = Record::query();

        if ($this->filterType === 'daily' && $this->dateFilter) {
            $records->whereDate('Date', $this->dateFilter);
        } elseif ($this->filterType === 'weekly') {
            $records->whereBetween('Date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->filterType === 'monthly') {
            $records->whereBetween('Date', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($this->filterType === 'custom' && $this->startDate && $this->endDate) {
            $records->whereBetween('Date', [$this->startDate, $this->endDate]);
        }

        $records = $records->orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.record-form', [
            'records' => $records,
            'dailySummary' => $dailySummary,
            'showBalance' => $this->filterType === 'daily',
        ]);
    }
}
