<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlendIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'blend_version_id',
        'material_id',
        'bottle_id',
        'drops',
        'dilution',
    ];

    public function blendVersion()
    {
        return $this->belongsTo(BlendVersion::class, 'blend_version_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function bottle()
    {
        return $this->belongsTo(Bottle::class);
    }

    public function assignBottle(Bottle $bottle): bool
    {
        if ($bottle->is_finished) {
            return false;
        }

        if (
            $this->bottle_id ||
            $this->material_id !== $bottle->material_id
        ) {
            return false;
        }
        $this->update([
            'bottle_id' => $bottle->id,
        ]);

        return true;
    }
}
