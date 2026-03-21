<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blend_ingredients', function (Blueprint $table) {
            $table->dropForeign(['bottle_id']);

            $table->foreign('bottle_id')
                ->references('id')
                ->on('bottles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blend_ingredients', function (Blueprint $table) {
            $table->dropForeign(['bottle_id']);

            $table->foreign('bottle_id')
                ->references('id')
                ->on('bottles')
                ->nullOnDelete();
        });
    }
};
