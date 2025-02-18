<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique();
            $table->timestamps();
        });

        DB::table('currencies')->insert([
            ['symbol' => 'TMT'],
            ['symbol' => 'USD']
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('currencies');
    }
};
