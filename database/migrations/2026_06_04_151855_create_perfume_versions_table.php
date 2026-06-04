<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfume_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfume_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('size');
            $table->unsignedInteger('concentration');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfume_versions');
    }
};
