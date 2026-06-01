<?php

namespace App\Http\Controllers;

use App\Models\BlendVersion;
use App\Models\Perfume;
use Illuminate\Http\Request;

class PerfumeController extends Controller
{
    public function create(Request $request, BlendVersion $version)
    {
        $problems = [];

        foreach ($version->ingredients as $ingredient) {
            if (! $ingredient->bottle) {
                $problems[] = "{$ingredient->material->name} is missing a bottle.";

                continue; // skip density check if bottle is missing
            }
            if (! $ingredient->bottle->density) {
                $problems[] = "{$ingredient->material->name} is missing density.";
            }
        }

        if (! empty($problems)) {
            return redirect()
                ->route('blends.show', $version->blend)
                ->withFragment('version-'.$version->id)
                ->with('version_id', $version->id)
                ->with('alerts', $problems);
        }

        return view('perfumes.create', compact('version'));
    }

    public function store(Request $request, BlendVersion $version)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'size' => 'required|numeric|min:0.1',
            'concentration' => 'required|numeric|min:0.1|max:100',
            'carrier_type' => 'nullable|string|in:alcohol,oil,solid',
        ]);

        if ($validated['carrier_type'] !== 'alcohol') {
            return back()
                ->withErrors(['carrier_type' => 'Only alcohol perfumes are currently supported.'])
                ->withInput();
        }

        $perfume = $version->perfumes()->create($validated);

        return redirect()->route('perfumes.show', $perfume);
    }

    public function show(Perfume $perfume)
    {

        $versionIngredients = $perfume
            ->version
            ->ingredientsOrdered(['material', 'bottle']);

        // Pure total  of essential oils in the original version
        $versionPureTotal = $versionIngredients->sum(function ($ingredient) {
            return $ingredient->pureAmount();
        });

        // Pure total of essential oils (pure drops)
        $perfumePureTotal = ($perfume->concentration / 100) * $perfume->size;

        $perfumeIngredients = $versionIngredients->map(function ($ingredient) use (
            $versionPureTotal,
            $perfumePureTotal,
            $perfume) {
            // Percentage of this ingredient in the formula
            $purePercentage = $ingredient->purePercentage($versionPureTotal);

            // Amount of this ingredient in ml
            $ingredientMl = ($purePercentage / 100) * $perfumePureTotal;

            // Convert ml to grams using bottle density
            $ingredientGrams = $ingredientMl * $ingredient->bottle->density;

            // Percentage of this ingredient in the final perfume
            $ingredientPercentage = ($ingredientMl / $perfume->size) * 100;

            return [
                'material' => $ingredient->material->name,
                'material_id' => $ingredient->material->id,
                'variant' => $ingredient->variant(),
                'percentage' => rtrim(rtrim(number_format($ingredientPercentage, 2, '.', ''), '0'), '.'),
                'grams' => rtrim(rtrim(number_format($ingredientGrams, 3, '.', ''), '0'), '.'),
            ];
        });

        // Alcohol calculation
        $alcoholMl = $perfume->size - $perfumePureTotal;

        // Approximate perfumers alcohol density
        $alcoholDensity = 0.85;
        $alcoholGrams = $alcoholMl * $alcoholDensity;

        // Add alcohol row
        $perfumeIngredients->push([
            'material' => 'Alcohol',
            'material_id' => null,
            'variant' => null,
            'percentage' => rtrim(rtrim(number_format((100 - $perfume->concentration), 2, '.', ''), '0'), '.'),
            'grams' => rtrim(rtrim(number_format($alcoholGrams, 3, '.', ''), '0'), '.'),
        ]);

        return view('perfumes.show', compact('perfume', 'perfumeIngredients'));
    }
}
