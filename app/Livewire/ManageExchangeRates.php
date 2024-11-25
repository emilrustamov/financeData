<?php

namespace App\Livewire;


use App\Models\ExchangeRate;
use Livewire\Component;

class ManageExchangeRates extends Component
{
    public $currencies;

    public function mount()
    {
        $this->currencies = ExchangeRate::all()->toArray();
    }

    public function updateRate($currencyId, $newRate)
    {
        $rate = ExchangeRate::find($currencyId);

        if ($rate) {
            $rate->update(['rate' => $newRate]);
            $this->currencies = ExchangeRate::all()->toArray(); // Обновляем список
        }
    }

    public function render()
    {
        return view('livewire.manage-exchange-rates');
    }
}
