<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashUserTable extends Migration
{
    /**
     * Выполнить миграцию.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cash_id');
            $table->timestamps();

            // Внешний ключ для user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Внешний ключ для cash_id
            $table->foreign('cash_id')->references('id')->on('cash')->onDelete('cascade');

            // Индексы для улучшения производительности
            $table->unique(['user_id', 'cash_id']);
        });
    }

    /**
     * Откатить миграцию.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_user');
    }
}
