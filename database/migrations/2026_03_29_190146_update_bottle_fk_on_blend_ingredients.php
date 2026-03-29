<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blend_ingredients', function (Blueprint $table) {
            // Drop existing foreign key
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('blend_ingredients_bottle_id_foreign');
            }

            // Recreate with RESTRICT
            $table->foreign('bottle_id')
                ->references('id')
                ->on('bottles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blend_ingredients', function (Blueprint $table) {
            // Drop existing foreign key
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('blend_ingredients_bottle_id_foreign');
            }

            // Revert with CASCADE
            $table->foreign('bottle_id')
                ->references('id')
                ->on('bottles')
                ->cascadeOnDelete();
        });
    }
};
