<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('records', function (Blueprint $table) {
            // Удаляем старую связь с cash_register
            $table->dropForeign(['cash_id']);
            $table->dropColumn('cash_id');
        });
    }

    public function down()
    {
        Schema::table('records', function (Blueprint $table) {
            // Восстанавливаем старую связь с cash_register
            $table->unsignedBigInteger('cash_id')->after('id'); // Добавляем колонку cash_id
            $table->foreign('cash_id')->references('id')->on('cash_register'); // Восстанавливаем внешний ключ
        });
    }
};
