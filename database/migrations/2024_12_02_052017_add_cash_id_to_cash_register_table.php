<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('cash_register', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_id')->nullable()->after('id'); // Замените 'id' на нужное поле, если необходимо
            $table->foreign('cash_id')->references('id')->on('cash')->onDelete('cascade'); // Установите внешнюю связь с таблицей 'cash'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_register', function (Blueprint $table) {
            $table->dropForeign(['cash_id']);
            $table->dropColumn('cash_id');
        });
    }
};
