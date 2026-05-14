<?php

use App\Models\BlendVersion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        BlendVersion::query()
            ->each(function ($version) {
                $version->update([
                    'version' => (int) $version->version,
                ]);
            });
    }

    public function down(): void
    {
        //
    }
};
