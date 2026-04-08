<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blends', function (Blueprint $table) {
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('blends', function (Blueprint $table) {
            $table->dropUnique('blends_user_id_name_unique');
        });
    }
};
