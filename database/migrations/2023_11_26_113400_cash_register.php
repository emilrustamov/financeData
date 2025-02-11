<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_id')->constrained('cashes')->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0); 
            $table->date('date'); 
            $table->timestamps();
            $table->softDeletes();
        });
        
    }


    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
