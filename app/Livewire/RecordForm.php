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
    public $templateIdToDelete = null;
    public $isModalOpen = false;
    public $object;
    public $filterType = 'daily'; // Тип фильтра: daily, weekly, monthly, custom
    public $startDate = null;    // Для пользовательского диапазона
    public $endDate = null;      // Для пользовательского диапазона
    public $availableCashRegisters = [];
    public $selectedCashRegister = null;
    public $singleCurrency = null; // Для хранения валюты единственной доступной кассы

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
        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter', 'selectedCashRegisterFilter']);
        $this->recordId = $id;
        $this->isTemplate = $isTemplate;

        // Загружаем доступные кассы независимо от фильтра
        $this->loadAvailableCashRegisters();

        if ($id) {
            // Логика редактирования записи или шаблона
            if ($isTemplate) {
                $template = Template::findOrFail($id);
                $this->titleTemplate = $template->title_template;
                $this->icon = $template->icon;
                $this->articleType = $template->ArticleType;
                $this->articleDescription = $template->ArticleDescription;
                $this->amount = $template->original_amount;
                $this->currency = $template->original_currency;
                $this->date = $template->Date;
                $this->exchangeRate = $template->ExchangeRate;
                $this->link = $template->Link;
                $this->object = $template->Object;
                $this->selectedCashRegister = $template->cash_id;
            } else {
                $record = Record::findOrFail($id);
                $this->articleType = $record->ArticleType;
                $this->articleDescription = $record->ArticleDescription;
                $this->amount = $record->original_amount;
                $this->currency = $record->original_currency;
                $this->date = $record->Date;
                $this->exchangeRate = $record->ExchangeRate;
                $this->link = $record->Link;
                $this->object = $record->Object;
                $this->selectedCashRegister = $record->cash_id;
            }
        } else {
            // Логика для новой записи
            $this->articleType = null;
            $this->articleDescription = null;
            $this->amount = null;
            $this->currency = null;
            $this->date = now()->format('Y-m-d');
            $this->exchangeRate = null;
            $this->link = null;
            $this->object = null;

            // Если доступна одна касса, устанавливаем её и валюту
            if ($this->availableCashRegisters->count() === 1) {
                $cashRegister = $this->availableCashRegisters->first();
                $this->selectedCashRegister = $cashRegister->id;  // Обновляем выбранную кассу
                $this->currency = $cashRegister->currency->currency;
            } else {
                $cashRegister = $this->availableCashRegisters->first();
                $this->selectedCashRegister = $cashRegister->id;
                $this->currency = null; // У администратора должен быть выбор
            }
        }

        $this->isModalOpen = true;
    }





    public function loadAvailableCashRegisters()
    {
        $user = Auth::user();

        if (!$user) {
            \Log::warning('User is not authenticated.');
            return;
        }

        if ($user->is_admin) {
            $this->availableCashRegisters = Cash::all(); // Администратор видит все кассы
        } else {
            $this->availableCashRegisters = $user->availableCashRegisters; // Для обычных пользователей доступ ограничен
        }

        // Логика установки валюты, если доступна одна касса
        if ($this->availableCashRegisters->count() === 1) {
            $cashRegister = $this->availableCashRegisters->first();
            $this->singleCurrency = $cashRegister->currency->currency;
            $this->currency = $cashRegister->currency->currency;
        } else {
            $this->singleCurrency = null;
            $this->currency = null;
        }
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
        ];

        if ($this->isTemplate) {
            $rules['titleTemplate'] = 'required|string|max:100';
            $rules['icon'] = 'required|string|max:255';
        } else {
            $rules['selectedCashRegister'] = 'required|exists:cash,id';
        }

        $this->validate($rules);

        // Если касса не выбрана и пользователь не администратор
        if (!$this->selectedCashRegister && !Auth::user()->is_admin) {
            session()->flash('error', 'Выберите кассу.');
            return;
        }

        // Устанавливаем дату
        $date = $this->date ?: now()->format('Y-m-d');

        // Получаем кассу, если выбрана
        $cashRegister = $this->selectedCashRegister ? Cash::find($this->selectedCashRegister) : null;

        // Если касса выбрана, проверяем её валюту и закрытие
        if ($cashRegister) {
            // Проверка на соответствие валюты записи валюте кассы
            if ($this->currency !== $cashRegister->currency->currency) {
                session()->flash('error', "Валюта записи ({$this->currency}) должна совпадать с валютой кассы ({$cashRegister->currency->currency}).");
                return;
            }

            // Проверка, закрыта ли касса на указанную дату
            if (CashRegister::where('cash_id', $this->selectedCashRegister)->whereDate('Date', $date)->exists()) {
                session()->flash('error', 'Касса уже закрыта за указанную дату. Изменения невозможны.');
                return;
            }
        }

        // Конвертация суммы
        $originalAmount = $this->amount;
        $originalCurrency = $this->currency;
        $convertedAmount = $originalAmount;

        if ($originalCurrency !== 'Манат') {
            try {
                $convertedAmount = $this->convertToBaseCurrency($originalAmount, $originalCurrency);
            } catch (\Exception $e) {
                session()->flash('error', $e->getMessage());
                return;
            }
        }

        // Подготовка данных для сохранения
        $data = [
            'ArticleType' => $this->articleType,
            'ArticleDescription' => $this->articleDescription,
            'Amount' => $convertedAmount,
            'Currency' => $originalCurrency,
            'original_amount' => $originalAmount,
            'original_currency' => $originalCurrency,
            'Date' => $date,
            'ExchangeRate' => $this->exchangeRate ?: null,
            'Link' => $this->link,
            'Object' => $this->object,
            'cash_id' => $this->selectedCashRegister,
        ];

        if ($this->isTemplate) {
            // Сохранение шаблона
            $data['title_template'] = $this->titleTemplate;
            $data['icon'] = $this->icon;
            $data['user_id'] = Auth::id();

            Template::updateOrCreate(['id' => $this->recordId], $data);
        } else {
            // Сохранение записи
            Record::updateOrCreate(['id' => $this->recordId], $data);
        }

        // Очистка формы после сохранения
        $this->resetExcept([
            'defaultExchangeRates',
            'templates',
            'availableIcons',
            'dateFilter',
            'selectedCashRegisterFilter',
        ]);

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
        $this->amount = $record->original_amount; // Оригинальная сумма
        $this->currency = $record->original_currency; // Оригинальная валюта
        $this->date = $record->Date;
        $this->exchangeRate = $record->ExchangeRate;
        $this->link = $record->Link;
        $this->object = $record->Object;
        $this->recordId = null; // Копия должна быть новой записью
        $this->isModalOpen = true;

        $availableCashRegisterIds = auth()->user()->availableCashRegisters()->pluck('cash.id')->toArray();
        $this->availableCashRegisters = Cash::whereIn('id', $availableCashRegisterIds)->get();

        if (in_array($record->cash_id, $availableCashRegisterIds)) {
            $this->selectedCashRegister = $record->cash_id;
        } else {
            $this->selectedCashRegister = $this->availableCashRegisters->first()->id ?? null;
            session()->flash('error', 'Касса записи недоступна. Выбрана первая доступная касса.');
        }
    }


    public function deleteRecord()
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'У вас нет доступа к удалению записей.');
        }

        // Удаляем запись по сохранённому ID
        Record::findOrFail($this->recordId)->delete();

        // Сбрасываем ID записи
        $this->recordId = null;

        // Отправляем сообщение об успехе
        session()->flash('message', 'Запись успешно удалена.');
    }



    public function confirmDeleteRecord($id)
    {
        // Сохраняем ID записи для удаления
        $this->recordId = $id;

        // Отправляем событие для отображения окна подтверждения на фронте
        $this->dispatch('show-delete-record-confirmation');
    }


    public function applyTemplate($id)
    {
        $template = Template::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        // Сброс всех полей, которые не нужны
        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons', 'dateFilter', 'selectedCashRegisterFilter']);
        $this->isTemplate = false;

        // Заполнение полей шаблона
        $this->titleTemplate = $template->title_template;
        $this->icon = $template->icon;
        $this->articleType = $template->ArticleType;
        $this->articleDescription = $template->ArticleDescription;
        $this->amount = $template->original_amount; // Оригинальная сумма
        $this->currency = $template->original_currency; // Оригинальная валюта
        $this->date = $template->Date;
        $this->exchangeRate = $template->ExchangeRate;
        $this->link = $template->Link;
        $this->object = $template->Object;

        // Устанавливаем кассу из шаблона
        $this->selectedCashRegister = $template->cash_id;

        // Загружаем доступные кассы для пользователя
        $this->loadAvailableCashRegisters();

        // Проверяем, доступна ли касса из шаблона для текущего пользователя
        if (!$this->availableCashRegisters->pluck('id')->contains($this->selectedCashRegister)) {
            $this->selectedCashRegister = $this->availableCashRegisters->first()->id ?? null;
        }

        // Открываем модалку
        $this->isModalOpen = true;
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
        // Создаём запросы для прихода и расхода
        $incomeQuery = Record::query()->where('ArticleType', 'Приход');
        $expenseQuery = Record::query()->where('ArticleType', 'Расход');

        $singleCashMode = $this->selectedCashRegisterFilter !== null;

        // Фильтрация по кассе
        if ($singleCashMode) {
            $cash = Cash::find($this->selectedCashRegisterFilter);

            if ($cash) {
                $currency = $cash->currency->currency;

                // Приходы и расходы за выбранный день
                if ($this->dateFilter) {
                    $incomeQuery->where('cash_id', $this->selectedCashRegisterFilter)
                        ->whereDate('Date', $this->dateFilter);
                    $expenseQuery->where('cash_id', $this->selectedCashRegisterFilter)
                        ->whereDate('Date', $this->dateFilter);
                } else {
                    $incomeQuery->where('cash_id', $this->selectedCashRegisterFilter);
                    $expenseQuery->where('cash_id', $this->selectedCashRegisterFilter);
                }

                $dailyIncome = $incomeQuery->sum('original_amount');
                $dailyExpense = $expenseQuery->sum('original_amount');
                $dailyBalance = $dailyIncome - $dailyExpense;

                // Итоговый баланс для выбранной кассы
                $totalIncome = Record::where('cash_id', $this->selectedCashRegisterFilter)
                    ->where('ArticleType', 'Приход')
                    ->sum('original_amount');
                $totalExpense = Record::where('cash_id', $this->selectedCashRegisterFilter)
                    ->where('ArticleType', 'Расход')
                    ->sum('original_amount');
                $totalBalance = $totalIncome - $totalExpense;

                return [
                    'income' => $dailyIncome,
                    'expense' => $dailyExpense,
                    'balance' => $dailyBalance,
                    'totalBalance' => $totalBalance,
                    'currency' => $currency,
                ];
            }
        }

        // Если все кассы выбраны
        $currency = 'Манат'; // Базовая валюта
        if ($this->dateFilter) {
            $incomeQuery->whereDate('Date', $this->dateFilter);
            $expenseQuery->whereDate('Date', $this->dateFilter);
        }

        $dailyIncome = $incomeQuery->sum('Amount');
        $dailyExpense = $expenseQuery->sum('Amount');
        $dailyBalance = $dailyIncome - $dailyExpense;

        // Итоговый баланс для всех касс
        $totalIncome = Record::where('ArticleType', 'Приход')->sum('Amount');
        $totalExpense = Record::where('ArticleType', 'Расход')->sum('Amount');
        $totalBalance = $totalIncome - $totalExpense;

        return [
            'income' => $dailyIncome,
            'expense' => $dailyExpense,
            'balance' => $dailyBalance,
            'totalBalance' => $totalBalance,
            'currency' => $currency,
        ];
    }


    public function calculateTotalBalance($cashId = null, $dateFilter = null)
    {
        $queryIncome = Record::query()->where('ArticleType', 'Приход');
        $queryExpense = Record::query()->where('ArticleType', 'Расход');

        if ($cashId) {
            $queryIncome->where('cash_id', $cashId);
            $queryExpense->where('cash_id', $cashId);

            $cash = Cash::find($cashId);
            if ($cash) {
                $currency = $cash->currency->currency;

                $totalIncome = $queryIncome->sum('original_amount');
                $totalExpense = $queryExpense->sum('original_amount');

                return [
                    'total' => $totalIncome - $totalExpense,
                    'currency' => $currency,
                ];
            }
        }

        if ($dateFilter) {
            $queryIncome->whereDate('Date', $dateFilter);
            $queryExpense->whereDate('Date', $dateFilter);
        }

        $totalIncome = $queryIncome->sum('Amount');
        $totalExpense = $queryExpense->sum('Amount');

        return [
            'total' => $totalIncome - $totalExpense,
            'currency' => 'Манат',
        ];
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
        $dailySummary = $this->getDailySummary(); // Получаем полный расчет
        $dailyBalance = $dailySummary['balance'] ?? 0; // Извлекаем только числовое значение баланса

        // Фиксируем баланс за выбранную дату и кассу
        CashRegister::create([
            'Date' => $date,
            'cash_id' => $this->selectedCashRegisterFilter,
            'balance' => $dailyBalance, // Указываем только числовое значение
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


    public function confirmDeleteTemplate($id)
    {
        $this->templateIdToDelete = $id;
        $this->dispatch('show-delete-template-confirmation');
    }

    public function deleteTemplate()
    {
        $template = Template::where('id', $this->templateIdToDelete)
            ->where('user_id', Auth::id())
            ->firstOrFail(); // Удаляем только свой шаблон
        $template->delete();

        $this->templates = Template::where('user_id', Auth::id())->get(); // Обновляем список шаблонов
        session()->flash('message', 'Шаблон успешно удалён.');

        // Сбрасываем ID шаблона
        $this->templateIdToDelete = null;
    }




    public function mount()
    {
        // Загружаем шаблоны текущего пользователя
        $this->templates = Template::where('user_id', Auth::id())->get();

        if (Auth::user()->is_admin) {
            // Администратор видит все кассы
            $this->availableCashRegisters = Cash::all();
        } else {
            // Обычный пользователь видит только доступные кассы
            $this->availableCashRegisters = Auth::user()->availableCashRegisters;
        }

        // Проверяем, является ли пользователь администратором
        if (Auth::user()->is_admin) {
            // Устанавливаем фильтр на все кассы (по умолчанию)
            $this->selectedCashRegisterFilter = null;
            $this->singleCurrency = null; // Админ может видеть все валюты
        } else {
            // Для обычных пользователей устанавливаем доступные кассы
            if ($this->availableCashRegisters->count() === 1) {
                $singleCash = $this->availableCashRegisters->first(); // Получаем объект первой кассы
                $this->selectedCashRegisterFilter = $singleCash->id; // Устанавливаем фильтр
                $this->singleCurrency = $singleCash->currency->currency; // Устанавливаем валюту этой кассы
            } else {
                $this->selectedCashRegisterFilter = null; // Если несколько касс, сбрасываем фильтр
                $this->singleCurrency = null; // Сбрасываем валюту
            }
        }

        // Загружаем курсы валют
        $this->defaultExchangeRates = ExchangeRate::pluck('rate', 'currency')->toArray();

        // Рассчитываем общий баланс
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

        if (Auth::user()->is_admin) {
            // Если пользователь — администратор
            if ($this->selectedCashRegisterFilter) {
                // Фильтрация по выбранной кассе, если указана
                $this->records->where('cash_id', $this->selectedCashRegisterFilter);
            }
        } else {
            // Для обычных пользователей — доступные кассы
            $accessibleCashRegisters = Auth::user()->availableCashRegisters;

            if ($accessibleCashRegisters->isNotEmpty()) {
                $accessibleCashRegisterIds = $accessibleCashRegisters->modelKeys(); // Получаем массив ID доступных касс
                $this->records->whereIn('cash_id', $accessibleCashRegisterIds);

                if ($this->selectedCashRegisterFilter) {
                    // Дополнительная фильтрация по выбранной кассе
                    $this->records->where('cash_id', $this->selectedCashRegisterFilter);
                }
            }
        }

        // Логируем выбранный фильтр кассы
        \Log::info('Filtered records by selected cash register filter: ', [$this->selectedCashRegisterFilter]);

        // Применяем пагинацию
        $this->records = $this->records->orderBy('created_at', 'desc')->paginate(20);
    }

    public function render()
    {
        // Обновляем список шаблонов
        $this->templates = Template::where('user_id', Auth::id())->get();

        // Проверяем роль пользователя и загружаем доступные кассы
        $accessibleCashRegisters = Auth::user()->is_admin
            ? Cash::all() // Администратор видит все кассы
            : Auth::user()->availableCashRegisters;

        // Если фильтруем по кассе
        $records = Record::query();
        if ($accessibleCashRegisters->isNotEmpty()) {
            $accessibleCashRegisterIds = $accessibleCashRegisters->pluck('id')->toArray();
            $records->whereIn('cash_id', $accessibleCashRegisterIds);
        }

        if ($this->selectedCashRegisterFilter) {
            // Применяем фильтрацию по выбранной кассе
            $records->where('cash_id', $this->selectedCashRegisterFilter);

            // Устанавливаем валюту выбранной кассы
            $selectedCash = $accessibleCashRegisters->firstWhere('id', $this->selectedCashRegisterFilter);
            $this->singleCurrency = $selectedCash ? $selectedCash->currency->currency : null;
        } else {
            // Сбрасываем валюту, если фильтр сброшен
            $this->singleCurrency = null;
        }

        // Применяем фильтрацию по дате
        if ($this->filterType === 'daily' && $this->dateFilter) {
            $records->whereDate('Date', $this->dateFilter);
        } elseif ($this->filterType === 'weekly') {
            $records->whereBetween('Date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->filterType === 'monthly') {
            $records->whereBetween('Date', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($this->filterType === 'custom' && $this->startDate && $this->endDate) {
            $records->whereBetween('Date', [$this->startDate, $this->endDate]);
        }

        // Пагинация и сортировка
        $records = $records->orderBy('created_at', 'desc')->paginate(20);

        // Рассчитываем ежедневные итоги
        $dailySummary = $this->getDailySummary();

        return view('livewire.record-form', [
            'records' => $records,
            'dailySummary' => $dailySummary,
            'showBalance' => $this->filterType === 'daily',
            'availableCashRegisters' => $accessibleCashRegisters,
        ]);
    }


}