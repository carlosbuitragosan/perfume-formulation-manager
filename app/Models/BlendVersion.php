<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlendVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'blend_id',
        'version',
    ];

    public function blend()
    {
        return $this->belongsTo(Blend::class);
    }

    public function ingredients()
    {
        return $this->hasMany(BlendIngredient::class);
    }

    public function ingredientsOrdered()
    {
        return $this->ingredients()
            ->with('material')
            ->get()
            ->sortBy(fn ($ingredient) => $ingredient->pyramidSortValue())
            ->values(); // reindex after sorting
    }

    public function formattedIngredients()
    {
        // Sort ingredients by pyramid order
        $ingredients = $this->ingredientsOrdered();

        // total of drops of PURE material (e.g. x3, 10 drops @25 each =  7.5)
        $pureTotal = $ingredients->sum(function ($ingredient) {
            return $ingredient->drops * ($ingredient->dilution / 100);
        });

        return $ingredients->map(function ($ingredient) use ($pureTotal) {
            // pure amount of material
            $pure = $ingredient->drops * ($ingredient->dilution / 100); // 2.5
            // percentage of pure material in the blend
            $purePercentage = $pureTotal > 0 ? ($pure / $pureTotal) * 100 : 0; // 33.33%

            // Returns a collection of arrays
            return [
                'blend_ingredient_id' => $ingredient->id,
                'material_id' => $ingredient->material_id,
                'material_name' => $ingredient->material->name,
                'bottle_id' => $ingredient->bottle_id,
                'drops' => (string) $ingredient->drops,
                'dilution' => $ingredient->dilution.'%',
                'pure_pct' => rtrim(rtrim(number_format($purePercentage, 2, '.', ''), '0'), '.').'%',
                'variant' => $ingredient->variant(),
            ];
        });
    }
}
