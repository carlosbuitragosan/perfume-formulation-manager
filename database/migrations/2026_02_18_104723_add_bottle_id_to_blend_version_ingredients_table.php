<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blend_version_ingredients', function (Blueprint $table) {
            $table->foreignId('bottle_id')
                ->nullable()
                ->constrained('bottles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blend_version_ingredients', function (Blueprint $table) {
            $table->dropForeign(['bottle_id']);
            $table->dropColumn('bottle_id');
        });
    }
};
