<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('blend_version_ingredients', 'blend_ingredients');
    }

    public function down(): void
    {
        Schema::rename('blend_ingredients', 'blend_version_ingredients');
    }
};
