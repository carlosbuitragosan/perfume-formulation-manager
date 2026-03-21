<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blend_ingredients', function (Blueprint $table) {
            // Drop old constraints from old table name
            $table->dropForeign('blend_version_ingredients_blend_version_id_foreign');
            $table->dropForeign('blend_version_ingredients_material_id_foreign');
            $table->dropForeign('blend_version_ingredients_bottle_id_foreign');

            // Recreate with correct names
            $table->foreign('blend_version_id')
                ->references('id')
                ->on('blend_versions')
                ->cascadeOnDelete();

            $table->foreign('material_id')
                ->references('id')
                ->on('materials')
                ->cascadeOnDelete();

            $table->foreign('bottle_id')
                ->references('id')
                ->on('bottles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blend_ingredients', function (Blueprint $table) {
            // Drop new constraints
            $table->dropForeign(['blend_version_id']);
            $table->dropForeign(['material_id']);
            $table->dropForeign(['bottle_id']);

            // Recreate old constraints (old names + old behavior)
            $table->foreign('blend_version_id', 'blend_version_ingredients_blend_version_id_foreign')
                ->references('id')
                ->on('blend_versions')
                ->cascadeOnDelete();

            $table->foreign('material_id', 'blend_version_ingredients_material_id_foreign')
                ->references('id')
                ->on('materials')
                ->cascadeOnDelete();

            $table->foreign('bottle_id', 'blend_version_ingredients_bottle_id_foreign')
                ->references('id')
                ->on('bottles')
                ->nullOnDelete();
        });
    }
};
