<?php

namespace App\Livewire;

use App\Models\Record;
use App\Models\Cash;
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
    public $totalBalance;
    public $titleTemplate, $icon;
    public $templates = [];
    public $recordId = null;
    public $isModalOpen = false;
    public $object;
    public $filterType = 'daily'; // Тип фильтра: daily, weekly, monthly, custom
    public $startDate = null;    // Для пользовательского диапазона
    public $endDate = null;      // Для пользовательского диапазона
    public $availableCashRegisters = [];
    public $selectedCashRegister = null;
    public $selectedCashRegisterFilter; // Для хранения выбранной кассы для фильтрации


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
        // Сброс состояния
        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter', 'selectedCashRegisterFilter']);
        $this->recordId = $id;
        $this->isTemplate = $isTemplate;

        if ($id) {
            if ($isTemplate) {
                // Загрузка данных шаблона
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

                // Для шаблона кассу не нужно выбирать, сбрасываем
                $this->selectedCashRegister = null;
            } else {
                // Загрузка данных записи
                $record = Record::findOrFail($id);
                $this->articleType = $record->ArticleType;
                $this->articleDescription = $record->ArticleDescription;
                $this->amount = $record->Amount;
                $this->currency = $record->Currency;
                $this->date = $record->Date;
                $this->exchangeRate = $record->ExchangeRate;
                $this->link = $record->Link;
                $this->object = $record->Object;

                // Устанавливаем выбранную кассу из записи
                $this->selectedCashRegister = $record->cash_id;
            }
        } else {
            // Для новой записи
            $this->articleType = null;
            $this->articleDescription = null;
            $this->amount = null;
            $this->currency = null;
            $this->date = now()->format('Y-m-d');
            $this->exchangeRate = null;
            $this->link = null;
            $this->object = null;
            $this->selectedCashRegister = null; // Новая запись — касса пока не выбрана
        }

        // Подгружаем доступные кассы для выбора через метод, учитывающий пользователя
        $this->loadAvailableCashRegisters();

        // Если касса не выбрана, установим первую доступную кассу
        if (!$this->selectedCashRegister && $this->availableCashRegisters->isNotEmpty()) {
            $this->selectedCashRegister = $this->availableCashRegisters->first()->id;
        }

        // Открываем модальное окно
        $this->isModalOpen = true;
    }


    public function loadAvailableCashRegisters()
    {
        // Проверяем, авторизован ли пользователь
        $user = Auth::user();

        if (!$user) {
            \Log::warning('User is not authenticated.');
            return;
        }

        // Если пользователь — администратор, выводим все кассы
        if ($user->is_admin) {
            $this->availableCashRegisters = Cash::all();  // Выводим все кассы для администратора
        } else {
            // Получаем кассы, к которым у пользователя есть доступ через таблицу cash_user
            $this->availableCashRegisters = $user->availableCashRegisters;  // Используем метод модели
        }

        // Проверка и логирование
        \Log::info('Available Cash Registers:', $this->availableCashRegisters->toArray());
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
        // Валидация формы
        $rules = [
            'articleType' => 'required|in:Приход,Расход',
            'articleDescription' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:Манат,Доллар,Рубль,Юань',
            'date' => 'required|date',
            'exchangeRate' => 'nullable|numeric|min:0',
            'link' => 'nullable|string',
            'selectedCashRegister' => 'required|exists:cash,id', // Проверка на существующую кассу
        ];
    
        if ($this->isTemplate) {
            $rules['titleTemplate'] = 'required|string|max:100';
            $rules['icon'] = 'required|string|max:255';
        }
    
        $this->validate($rules);
    
        // Определяем дату для записи
        $date = $this->date ?: now()->format('Y-m-d');
    
        // Получаем кассу по выбранному идентификатору
        $cashRegister = Cash::find($this->selectedCashRegister);
    
        // Если касса не найдена, выводим ошибку
        if (!$cashRegister) {
            session()->flash('error', 'Выбранная касса недоступна.');
            return;
        }
    
        // Если касса закрыта за этот день, выводим ошибку
        if ($cashRegister->is_closed) {
            session()->flash('error', 'Касса за этот день уже закрыта. Невозможно выполнить операцию.');
            return;
        }
    
        // Сохраняем оригинальные данные
        $originalAmount = $this->amount;
        $originalCurrency = $this->currency;
    
        // Конвертируем сумму в манаты
        $convertedAmount = $this->convertToBaseCurrency($originalAmount, $originalCurrency);
    
        // Подготовка данных для записи
        $data = [
            'ArticleType' => $this->articleType,
            'ArticleDescription' => $this->articleDescription,
            'Amount' => $convertedAmount, // Конвертированная сумма в манатах
            'Currency' => 'Манат', // Валюта для расчетов
            'original_amount' => $originalAmount, // Оригинальная сумма
            'original_currency' => $originalCurrency, // Оригинальная валюта
            'Date' => $this->date,
            'ExchangeRate' => $this->exchangeRate ?: null,
            'Link' => $this->link,
            'Object' => $this->object,
            'cash_id' => $this->selectedCashRegister, // Привязываем запись к кассе
        ];
    
        // Если используется шаблон, добавляем дополнительные поля
        if ($this->isTemplate) {
            $data['title_template'] = $this->titleTemplate;
            $data['icon'] = $this->icon;
            $data['user_id'] = Auth::id();
    
            // Сохраняем или обновляем шаблон
            Template::updateOrCreate(
                ['id' => $this->recordId],
                $data
            );
        } else {
            // Сохраняем или обновляем запись
            Record::updateOrCreate(
                ['id' => $this->recordId],
                $data
            );
        }
    
        // Сбрасываем форму и закрываем модальное окно
        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter', 'selectedCashRegisterFilter']);
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
        $this->calculateTotalBalance();
    }





    public function copyRecord($id)
    {
        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter', 'selectedCashRegisterFilter']);

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
        // Получаем доступные кассы
        $availableCashRegisterIds = auth()->user()->availableCashRegisters()->pluck('cash.id')->toArray();
        \Log::debug('Available Cash Registers WHILE COPYING: ', $availableCashRegisterIds);

        // Устанавливаем доступные кассы в компонент
        $this->availableCashRegisters = Cash::whereIn('id', $availableCashRegisterIds)->get();

        // Проверяем, доступна ли касса из оригинальной записи
        if (in_array($record->cash_id, $availableCashRegisterIds)) {
            $this->selectedCashRegister = $record->cash_id;
        } else {
            $this->selectedCashRegister = $this->availableCashRegisters->first()->id ?? null; // Первая доступная касса
            session()->flash('error', 'Касса записи недоступна. Выбрана первая доступная касса.');
        }
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
        $template = Template::where('id', $id)->where('user_id', Auth::id())->firstOrFail(); // Только свои шаблоны

        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter', 'selectedCashRegisterFilter']);
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

        // Получаем доступные кассы, к которым пользователь имеет доступ
        $availableCashRegisterIds = auth()->user()->availableCashRegisters()->pluck('cash.id')->toArray();
        \Log::debug('Available Cash Registers WHILE APPLYING TEMPLATE: ', $availableCashRegisterIds);

        // Устанавливаем доступные кассы в компонент
        $this->availableCashRegisters = Cash::whereIn('id', $availableCashRegisterIds)->get();

        // Устанавливаем выбранную кассу, если шаблон связан с доступной кассой
        if (in_array($template->cash_id, $availableCashRegisterIds)) {
            $this->selectedCashRegister = $template->cash_id; // Привязываем кассу из шаблона
        } else {
            // Если касса недоступна, выбираем первую доступную кассу
            $this->selectedCashRegister = $this->availableCashRegisters->first()->id ?? null;
            // session()->flash('error', 'Касса шаблона недоступна. Выбрана первая доступная касса.');
        }
    }

    private function convertToBaseCurrency($amount, $currency)
    {
        // Получаем курс валюты относительно доллара
        $rate = ExchangeRate::where('currency', $currency)->value('rate');

        if (!$rate) {
            throw new \Exception("Курс для валюты {$currency} не найден.");
        }

        // Конвертируем сумму в доллары
        $amountInDollars = $amount / $rate;

        // Получаем курс Манатов относительно доллара
        $baseRate = ExchangeRate::where('currency', 'Манат')->value('rate');

        if (!$baseRate) {
            throw new \Exception("Курс для Маната не найден.");
        }

        // Конвертируем из долларов в Манаты
        return $amountInDollars * $baseRate;
    }




    public function getDailySummary()
    {
        $incomeQuery = Record::query();
        $expenseQuery = Record::query();

        // Если фильтрация по кассе выбрана
        if ($this->selectedCashRegisterFilter) {
            // Фильтруем по полю cash_id, которое связано с таблицей Cash
            $incomeQuery->where('cash_id', $this->selectedCashRegisterFilter);
            $expenseQuery->where('cash_id', $this->selectedCashRegisterFilter);
        }

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

        // Баланс за выбранный день
        $dailyBalance = $this->calculateTotalBalance($this->selectedCashRegisterFilter, $this->dateFilter);

        // Текущий итоговый баланс (без фильтрации по дате)
        $totalBalance = $this->calculateTotalBalance($this->selectedCashRegisterFilter);


        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $dailyBalance,
            'totalBalance' => $totalBalance,
        ];
    }


    public function calculateTotalBalance($cashId = null, $dateFilter = null)
    {
        $queryIncome = Record::query()->where('ArticleType', 'Приход');
        $queryExpense = Record::query()->where('ArticleType', 'Расход');

        if ($cashId) {
            $queryIncome->where('cash_id', $cashId);
            $queryExpense->where('cash_id', $cashId);
        }

        if ($dateFilter) {
            $queryIncome->whereDate('Date', $dateFilter);
            $queryExpense->whereDate('Date', $dateFilter);
        }

        $incomeRecords = $queryIncome->get();
        $expenseRecords = $queryExpense->get();

        $totalIncome = $incomeRecords->sum(function ($record) {
            return $this->convertToBaseCurrency($record->Amount, $record->Currency);
        });

        $totalExpense = $expenseRecords->sum(function ($record) {
            return $this->convertToBaseCurrency($record->Amount, $record->Currency);
        });

        // Итоговый баланс: приходы - расходы
        return $totalIncome - $totalExpense;
    }




    public function closeCashRegister()
    {
        // Проверяем, выбрана ли дата, если нет — используем текущую дату
        $date = $this->dateFilter ?: now()->format('Y-m-d');

        // Проверяем, выбрана ли касса
        if (!$this->selectedCashRegisterFilter) {
            session()->flash('error', 'Выберите кассу для закрытия.');
            return;
        }

        // Проверяем, если дата в будущем — запрет закрытия
        if (Carbon::parse($date)->isFuture()) {
            session()->flash('error', 'Нельзя закрыть кассу за будущий день.');
            return;
        }

        // Проверяем, закрыта ли касса за выбранную дату
        if (CashRegister::where('cash_id', $this->selectedCashRegisterFilter)->whereDate('Date', $date)->exists()) {
            session()->flash('error', 'Касса уже закрыта за выбранный день.');
            return;
        }

        // Рассчитываем баланс за выбранный день и выбранную кассу
        $dailyBalance = $this->calculateTotalBalance($this->selectedCashRegisterFilter, $date);

        // Фиксируем баланс за выбранную дату и кассу
        CashRegister::create([
            'Date' => $date,
            'cash_id' => $this->selectedCashRegisterFilter,
            'balance' => $dailyBalance,
        ]);

        // Обновляем общий баланс
        $this->calculateTotalBalance();

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
        $template = Template::where('id', $id)->where('user_id', Auth::id())->firstOrFail(); // Удаляем только свой шаблон
        $template->delete();

        $this->templates = Template::where('user_id', Auth::id())->get(); // Обновляем список шаблонов
        session()->flash('message', 'Шаблон успешно удалён.');
    }



    public function mount()
    {
        $this->templates = Template::where('user_id', Auth::id())->get(); // Только шаблоны текущего пользователя
        $this->availableCashRegisters = Auth::user()->availableCashRegisters;
        if (Auth::user()->is_admin) {
            // Устанавливаем фильтр на все кассы (по умолчанию)
            $this->selectedCashRegisterFilter = null;
        } else {
            // Для обычных пользователей устанавливаем доступные кассы
            $this->selectedCashRegisterFilter = Auth::user()->availableCashRegisters->pluck('id');
        }

        $this->defaultExchangeRates = ExchangeRate::pluck('rate', 'currency')->toArray();
        $this->availableIcons;
        $this->calculateTotalBalance();
    }

    public function updatedSelectedCashRegisterFilter($value)
    {
        \Log::info('Selected Cash Register Filter changed to: ' . $value);

        // Обновляем фильтрацию по кассе
        $this->applyCashRegisterFilter();
    }

    public function applyCashRegisterFilter()
    {
        // Сбрасываем все записи и начинаем фильтрацию заново
        $this->records = Record::query();

        // Получаем доступные кассы для пользователя через связь с таблицей cash_user
        $accessibleCashRegisters = Auth::user()->availableCashRegisters;

        // Фильтрация по доступным кассам
        if ($accessibleCashRegisters->isNotEmpty()) {
            $accessibleCashRegisterIds = $accessibleCashRegisters->modelKeys(); // Получаем массив ID доступных касс
            $this->records->whereIn('cash_id', $accessibleCashRegisterIds);
        }

        // Фильтрация по выбранной кассе, если она указана
        if ($this->selectedCashRegisterFilter) {
            $this->records->where('cash_id', $this->selectedCashRegisterFilter);
        }

        // Логируем выбранный фильтр кассы
        \Log::info('Filtered records by selected cash register filter: ', [$this->selectedCashRegisterFilter]);

        // Применяем пагинацию
        $this->records = $this->records->orderBy('created_at', 'desc')->paginate(20);
    }

    public function render()
    {
        $this->templates = Template::where('user_id', Auth::id())->get(); // Обновляем список шаблонов

        $dailySummary = $this->getDailySummary();

        $records = Record::query();

        // Получаем доступные кассы для пользователя через связь с таблицей cash_user
        $accessibleCashRegisters = Auth::user()->availableCashRegisters;

        // Проверяем, если у пользователя есть доступные кассы
        if ($accessibleCashRegisters->isNotEmpty()) {
            // Получаем массив ID доступных касс
            $accessibleCashRegisterIds = $accessibleCashRegisters->modelKeys();

            // Фильтруем записи, относящиеся к этим кассам
            $records->whereIn('cash_id', $accessibleCashRegisterIds);
        }

        // Фильтрация по выбранной кассе, если она указана
        if ($this->selectedCashRegisterFilter) {
            $records->where('cash_id', $this->selectedCashRegisterFilter);
        }

        // Логируем выбранный фильтр кассы
        \Log::info('Filtered records by selected cash register filter: ', [$this->selectedCashRegisterFilter]);

        // Применяем фильтрацию по дате (если указано)
        if ($this->filterType === 'daily' && $this->dateFilter) {
            $records->whereDate('Date', $this->dateFilter);
        } elseif ($this->filterType === 'weekly') {
            $records->whereBetween('Date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->filterType === 'monthly') {
            $records->whereBetween('Date', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($this->filterType === 'custom' && $this->startDate && $this->endDate) {
            $records->whereBetween('Date', [$this->startDate, $this->endDate]);
        }

        // Применяем пагинацию
        $records = $records->orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.record-form', [
            'records' => $records,
            'dailySummary' => $dailySummary,
            'showBalance' => $this->filterType === 'daily',
            'availableCashRegisters' => $accessibleCashRegisters, // Передаем кассы в шаблон для фильтра
        ]);
    }



}