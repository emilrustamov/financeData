<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('objects', function (Blueprint $table) {
            $table->json('users');
        });

        Schema::table('object_categories', function (Blueprint $table) {
            $table->json('users');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->json('users');
        });
    }


    public function down(): void
    {
        Schema::table('objects', function (Blueprint $table) {
            $table->dropColumn('users');
        });

        Schema::table('object_categories', function (Blueprint $table) {
            $table->dropColumn('users');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('users');
        });
    }
};
