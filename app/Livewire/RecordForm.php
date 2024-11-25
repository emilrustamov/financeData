<?php

namespace App\Livewire;

use App\Models\Record;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\ExchangeRate;
use Livewire\WithPagination;



class RecordForm extends Component
{
    use WithPagination;
    public $type, $articleType, $articleDescription, $amount, $currency, $date, $exchangeRate, $link;
    public $recordId = null;
    public $isModalOpen = false;
    public $object;
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
    ];

    public function openModal($id = null)
    {
        $this->resetExcept(['defaultExchangeRates']);
        $this->recordId = $id;

        if ($id) {
            // Редактирование записи
            if (!Auth::user()->is_admin) {
                abort(403, 'У вас нет доступа к редактированию записей.');
            }

            $record = Record::findOrFail($id);

            $this->articleType = $record->ArticleType;
            $this->articleDescription = $record->ArticleDescription;
            $this->amount = $record->Amount;
            $this->currency = $record->Currency;
            $this->date = $record->Date;
            $this->exchangeRate = $record->ExchangeRate;
            $this->link = $record->Link;
            $this->object = $record->Object;
        } else {
            // Новая запись: если выбрана валюта, подставляем курс
            if ($this->currency && isset($this->defaultExchangeRates[$this->currency])) {
                $this->exchangeRate = $this->defaultExchangeRates[$this->currency];
            }
        }

        $this->isModalOpen = true;
    }
    public function updatedCurrency($value)
    {
        // Проверяем, есть ли курс для выбранной валюты
        if (isset($this->defaultExchangeRates[$value])) {
            $this->exchangeRate = $this->defaultExchangeRates[$value];
        } else {
            $this->exchangeRate = null; // Если валюты нет в массиве, очищаем поле
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
        if ($this->recordId && !Auth::user()->is_admin) {
            abort(403, 'У вас нет доступа к редактированию записей.');
        }
        $this->validate();


        Record::updateOrCreate(
            ['id' => $this->recordId],
            [

                'ArticleType' => $this->articleType,
                'ArticleDescription' => $this->articleDescription,
                'Amount' => $this->amount,
                'Currency' => $this->currency,
                'Date' => $this->date,
                'ExchangeRate' => $this->exchangeRate ?: null,
                'Link' => $this->link,
                'Object' => $this->object,
            ]
        );

        $this->reset();
        session()->flash('message', $this->recordId ? 'Запись успешно обновлена.' : 'Запись успешно добавлена.');
        $this->closeModal();
    }
    public function copyRecord($id)
    {
        $this->resetExcept(['defaultExchangeRates']); // Сброс, кроме курсов обмена

        $record = Record::findOrFail($id); // Получаем выбранную запись

        // Заполняем форму данными из выбранной записи
        $this->articleType = $record->ArticleType;
        $this->articleDescription = $record->ArticleDescription;
        $this->amount = $record->Amount;
        $this->currency = $record->Currency;
        $this->date = $record->Date;
        $this->exchangeRate = $record->ExchangeRate;
        $this->link = $record->Link;
        $this->object = $record->Object;

        $this->recordId = null; // Убираем ID, чтобы сохранить новую запись

        $this->isModalOpen = true; // Открываем модальное окно
    }


    public function deleteRecord($id)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'У вас нет доступа к удалению записей.');
        }
        Record::findOrFail($id)->delete();
        session()->flash('message', 'Запись успешно удалена.');
    }


    public function mount()
    {
        $this->defaultExchangeRates = ExchangeRate::pluck('rate', 'currency')->toArray();
    }

    public function render()
    {
        $records = Record::orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.record-form', compact('records'));
    }
}
