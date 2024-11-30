<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCashRegisterTable extends Migration
{
    /**
     * Запуск миграции.
     *
     * @return void
     */
    public function up()
    {
        // Удаляем внешний ключ
        Schema::table('cash_register', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // удаляем внешний ключ
        });

        // Удаляем колонку
        Schema::table('cash_register', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }

    public function down()
    {
        // Восстанавливаем колонку, если нужно откатить миграцию
        Schema::table('cash_register', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}

