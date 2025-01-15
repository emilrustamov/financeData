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
        Schema::table('records', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_id')->nullable()->after('id');
            $table->foreign('cash_id')->references('id')->on('cash')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropForeign(['cash_id']);
            $table->dropColumn('cash_id');
        });
    }
};
