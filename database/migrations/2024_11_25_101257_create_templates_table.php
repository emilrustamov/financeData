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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('title_template');
            $table->string('icon');
            $table->string('ArticleType', 100)->nullable(); // Тип статьи
            $table->string('ArticleDescription', 255)->nullable(); // Описание статьи
            $table->decimal('Amount', 10, 2)->nullable(); // Сумма
            $table->string('Currency', 10)->nullable(); // Валюта
            $table->date('Date')->nullable(); // Дата
            $table->decimal('ExchangeRate', 10, 2)->nullable(); // Курс обмена
            $table->text('Link')->nullable(); // Ссылка
            $table->string('Object')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
