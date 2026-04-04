<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blend_ingredients', function (Blueprint $table) {
            // Drop existing foreign key
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('blend_ingredients_material_id_foreign');
            }

            // Recreate with RESTRICT
            $table->foreign('material_id')
                ->references('id')
                ->on('materials')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blend_ingredients', function (Blueprint $table) {
            // Drop existing foreign key
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('blend_ingredients_material_id_foreign');
            }

            // Revert with CASCADE
            $table->foreign('material_id')
                ->references('id')
                ->on('materials')
                ->cascadeOnDelete();
        });
    }
};
