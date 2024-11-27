<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_register', function (Blueprint $table) {
            $table->id(); // Поле ID
            $table->string('ArticleType', 50); // Тип (Приход или Расход)
            $table->decimal('Amount', 10, 2); // Сумма
            $table->string('Currency', 10); // Валюта
            $table->decimal('initial_balance', 10, 2)->default(0);
            $table->date('Date'); // Дата операции
            $table->timestamps(); // Поля created_at и updated_at
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
