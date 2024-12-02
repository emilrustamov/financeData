<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('records', function (Blueprint $table) {
            // Добавляем новую связь с cash
            $table->unsignedBigInteger('cash_id')->after('id'); // Добавляем поле cash_id
            $table->foreign('cash_id')->references('id')->on('cash'); // Устанавливаем внешний ключ
        });
    }

    public function down()
    {
        Schema::table('records', function (Blueprint $table) {
            // Удаляем колонку cash_id
            $table->dropForeign(['cash_id']);
            $table->dropColumn('cash_id');
        });
    }
};
