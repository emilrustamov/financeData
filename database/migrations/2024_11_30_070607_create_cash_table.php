<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashTable extends Migration
{
    /**
     * Выполнить миграцию.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // название кассы
            $table->timestamps();
        });
    }

    /**
     * Откатить миграцию.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash');
    }
}
