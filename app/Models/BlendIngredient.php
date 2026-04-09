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

    public function pyramidSortValue()
    {
        // Return an empty array if material has no values
        $pyramid = $this->material->pyramid ?? [];

        // Sort alphabetically to ensure consistency
        sort($pyramid);

        // Turn array into a string e.g. heart-top
        $key = implode('-', $pyramid);

        return match ($key) {
            'top' => 1,
            'heart-top' => 2,
            'heart' => 3,
            'base-heart' => 4,
            'base-heart-top' => 5,
            'base' => 6,
            default => 999,
        };
    }
}
