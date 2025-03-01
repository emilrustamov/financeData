<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cashes', function (Blueprint $table) {
            $table->string('color')->nullable()->after('currency_id');
        });
    }

    public function down()
    {
        Schema::table('cashes', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
