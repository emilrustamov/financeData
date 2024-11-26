<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CashRegister;

class InitialBalanceSeeder extends Seeder
{
    public function run()
    {
        // Установите начальный баланс только для текущей даты
        CashRegister::updateOrCreate(
            ['Date' => '2000-01-01'],
            ['balance' => 1000.00] // Укажите ваш начальный баланс
        );
    }
}
