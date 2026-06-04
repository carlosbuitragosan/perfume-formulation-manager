<?php

use App\Models\BlendVersion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        BlendVersion::query()
            ->each(function ($blendVersion) {
                $blendVersion->update([
                    'version' => (int) $blendVersion->version,
                ]);
            });
    }

    public function down(): void
    {
        //
    }
};
