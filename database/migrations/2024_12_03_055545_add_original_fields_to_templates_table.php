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
        Schema::table('templates', function (Blueprint $table) {
            $table->decimal('original_amount', 15, 2)->nullable()->after('Amount');
            $table->string('original_currency', 10)->nullable()->after('Currency');
        });
    }

    public function down()
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['original_amount', 'original_currency']);
        });
    }

};
