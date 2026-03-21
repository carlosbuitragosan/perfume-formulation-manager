<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bottles', function (Blueprint $table) {
            $table->renameColumn('is_active', 'is_finished');
        });

        DB::table('bottles')->update([
            'is_finished' => DB::raw('NOT is_finished'),
        ]);
    }

    public function down(): void
    {
        DB::table('bottles')->update([
            'is_finished' => DB::raw('NOT is_finished'),
        ]);

        Schema::table('bottles', function (Blueprint $table) {
            $table->renameColumn('is_finished', 'is_active');
        });

    }
};
