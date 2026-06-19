<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class perfumeVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'perfume_id',
        'size',
        'concentration',
    ];

    public function perfume()
    {
        return $this->belongsTo(Perfume::class);
    }

    public function breakdown()
    {
        // Get ingredients
        $blendVersionIngredients = $this
            ->perfume
            ->blendVersion
            ->ingredientsOrdered(['material', 'bottle']);

        // Pure total  of essential oils in the original version
        $blendVersionPureTotal = $blendVersionIngredients->sum(function ($ingredient) {
            return $ingredient->pureAmount();
        });

        // Pure total of essential oils (pure drops)
        $perfumePureTotal = ($this->concentration / 100) * $this->size;

        $perfumeVersionIngredients = $blendVersionIngredients->map(function ($ingredient) use (
            $blendVersionPureTotal,
            $perfumePureTotal
        ) {
            // Percentage of this ingredient in the formula
            $purePercentage = $ingredient->purePercentage($blendVersionPureTotal);

            // Amount of this ingredient in ml
            $ingredientMl = ($purePercentage / 100) * $perfumePureTotal;

            // Convert ml to grams using bottle density
            $ingredientGrams = $ingredientMl * $ingredient->bottle->density;

            // Percentage of this ingredient in the final perfume
            $ingredientPercentage = ($ingredientMl / $this->size) * 100;

            return [
                'material' => $ingredient->material->name,
                'material_id' => $ingredient->material->id,
                'variant' => $ingredient->variant(),
                'percentage' => rtrim(rtrim(number_format($ingredientPercentage, 2, '.', ''), '0'), '.'),
                'grams' => rtrim(rtrim(number_format($ingredientGrams, 3, '.', ''), '0'), '.'),
            ];
        });

        // Alcohol calculation
        $alcoholMl = $this->size - $perfumePureTotal;

        // Approximate perfumers alcohol density
        $alcoholDensity = 0.85;
        $alcoholGrams = $alcoholMl * $alcoholDensity;

        // Add alcohol row
        $perfumeVersionIngredients->push([
            'material' => 'Alcohol',
            'material_id' => null,
            'variant' => 'alcohol',
            'percentage' => rtrim(rtrim(number_format((100 - $this->concentration), 2, '.', ''), '0'), '.'),
            'grams' => rtrim(rtrim(number_format($alcoholGrams, 3, '.', ''), '0'), '.'),
        ]);

        return [
            'version' => $this,
            'ingredients' => $perfumeVersionIngredients,
        ];
    }
}
