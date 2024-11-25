<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ExchangeRate;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $currencies = [
            ['currency' => 'Манат', 'rate' => 19.75],
            ['currency' => 'Доллар', 'rate' => 1.00],
            ['currency' => 'Рубль', 'rate' => 96.00],
            ['currency' => 'Юань', 'rate' => 7.15],
        ];

        foreach ($currencies as $currency) {
            ExchangeRate::updateOrCreate(['currency' => $currency['currency']], ['rate' => $currency['rate']]);
        }
    }
}
