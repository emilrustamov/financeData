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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('title_template');
            $table->string('icon');
            $table->foreignId('cash_id')->constrained('cashes')->onDelete('cascade');
            $table->foreignId('object_id')->nullable()->constrained('objects')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('object_categories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('type')->default(1);
            $table->string('description', 255)->nullable();
            $table->unsignedDecimal('amount', 10, 2)->default(0);
            $table->date('date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
