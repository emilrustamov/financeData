<?php

namespace App\Livewire;

use App\Models\Record;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\ExchangeRate;
use Livewire\WithPagination;
use App\Models\Template;
use Illuminate\Support\Facades\File;



class RecordForm extends Component
{
    use WithPagination;
    public $articleType, $articleDescription, $amount, $currency, $date, $exchangeRate, $link;
    public $isTemplate = false;
    public $titleTemplate, $icon;
    public $templates = [];
    public $recordId = null;
    public $isModalOpen = false;
    public $object;
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
    ]; // сделать пустым, если используется метод getAvailableIcons


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


    public function openModal($id = null)
    {
        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons']);
        $this->isTemplate = false;
        $this->recordId = $id;

        if ($id) {
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
            if ($this->currency && isset($this->defaultExchangeRates[$this->currency])) {
                $this->exchangeRate = $this->defaultExchangeRates[$this->currency];
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

            // Создаем новую запись без обновления
            Template::create($data);
        } else {
            // Создаем или обновляем запись для Record
            Record::updateOrCreate(
                ['id' => $this->recordId],
                $data
            );
        }

        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons']);
        session()->flash('message', $this->recordId ? 'Запись успешно обновлена.' : 'Запись успешно добавлена.');
        $this->closeModal();
    }



    public function copyRecord($id)
    {
        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons']);

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

        $this->resetExcept(['defaultExchangeRates', 'templates', 'availableIcons']);
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



    public function mount()
    {

        $this->templates = Template::all();
        $this->defaultExchangeRates = ExchangeRate::pluck('rate', 'currency')->toArray();
        $this->availableIcons;
   
    }


    public function render()
    {
        $query = Record::query();
    
        // if ($this->dateFilter) {
        //     $query->whereDate('Date', $this->dateFilter);
        // }
    
        $records = $query->orderBy('created_at', 'desc')->paginate(20);
    
        return view('livewire.record-form', compact('records'));
    }
    
}
