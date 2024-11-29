<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CashRegister;
use App\Models\User;

class InitialBalanceSeeder extends Seeder
{
    public function run()
    {
        // Получаем администратора или создаем его
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'is_admin' => true,
            ]
        );

        // Установите начальный баланс для администратора
        CashRegister::updateOrCreate(
            ['Date' => '2000-01-01', 'user_id' => $admin->id],
            ['balance' => 1000.00] // Укажите ваш начальный баланс
        );

        // Пример добавления кассы для обычного пользователя (если нужно)
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Regular User',
                'password' => bcrypt('password'),
                'is_admin' => false,
            ]
        );

        CashRegister::updateOrCreate(
            ['Date' => now()->format('Y-m-d'), 'user_id' => $user->id],
            ['balance' => 500.00] // Укажите баланс для обычного пользователя
        );
    }
}
