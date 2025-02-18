<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cashes', function (Blueprint $table) {
            $table->foreignId('currency_id')->after('title')->nullable()->constrained('currencies')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('cashes', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
