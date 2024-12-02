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
        Schema::table('cash', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('id');

            // Настройка внешнего ключа на таблицу exchange_rates
            $table->foreign('currency_id')
                ->references('id')
                ->on('exchange_rates')
                ->onDelete('set null'); // Если запись удалена, поле currency_id станет null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash', function (Blueprint $table) {
            //
        });
    }
};
