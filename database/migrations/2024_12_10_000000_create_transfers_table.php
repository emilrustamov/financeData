<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration  
{
    public function up()
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_cash_id')->constrained('cashes')->onDelete('cascade');
            $table->foreignId('to_cash_id')->constrained('cashes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('from_record_id')->constrained('records')->onDelete('cascade');
            $table->foreignId('to_record_id')->constrained('records')->onDelete('cascade');
            $table->unsignedBigInteger('amount');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transfers');
    }
};
