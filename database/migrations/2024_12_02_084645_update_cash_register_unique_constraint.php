<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('cash_register', function (Blueprint $table) {
            // Удаляем текущее уникальное ограничение на `Date`
            $table->dropUnique('cash_register_date_unique');
    
            // Добавляем новое уникальное ограничение на сочетание `Date` и `cash_id`
            $table->unique(['Date', 'cash_id'], 'cash_register_date_cash_id_unique');
        });
    }
    
    public function down()
    {
        Schema::table('cash_register', function (Blueprint $table) {
            // Удаляем новое ограничение
            $table->dropUnique('cash_register_date_cash_id_unique');
    
            // Восстанавливаем старое ограничение только на `Date`
            $table->unique('Date', 'cash_register_date_unique');
        });
    }
};
