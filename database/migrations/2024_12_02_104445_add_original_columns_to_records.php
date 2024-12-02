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
        Schema::table('records', function (Blueprint $table) {
            $table->decimal('original_amount', 15, 2)->nullable()->after('Amount');
            $table->string('original_currency', 10)->nullable()->after('original_amount');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            //
        });
    }
};
