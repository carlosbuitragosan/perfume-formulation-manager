<?php

namespace App\Http\Controllers;

use App\Http\Requests\Perfume\UpdatePerfumeRequest;
use App\Models\BlendVersion;
use App\Models\Perfume;
use Illuminate\Http\Request;

class PerfumeController extends Controller
{
    public function index()
    {
        $perfumes = Perfume::all();

        return view('perfumes.index', compact('perfumes'));
    }

    public function create(Request $request, BlendVersion $blendVersion)
    {
        $problems = [];

        foreach ($blendVersion->ingredients as $ingredient) {
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
                ->route('blends.show', $blendVersion->blend)
                ->withFragment('version-'.$blendVersion->id)
                ->with('version_id', $blendVersion->id)
                ->with('alerts', $problems);
        }

        return view('perfumes.create', compact('blendVersion'));
    }

    public function store(Request $request, BlendVersion $blendVersion)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'size' => 'required|numeric|min:0.1',
            'concentration' => 'required|numeric|min:0.1|max:100',
        ]);

        $perfume = $blendVersion->perfumes()->create([
            'name' => $validated['name'],
        ]);

        $perfume->versions()->create([
            'size' => $validated['size'],
            'concentration' => $validated['concentration'],
        ]);

        return redirect()->route('perfumes.show', $perfume);
    }

    public function show(Perfume $perfume)
    {
        $blendVersionIngredients = $perfume
            ->blendVersion
            ->ingredientsOrdered(['material', 'bottle']);

        // Pure total  of essential oils in the original version
        $blendVersionPureTotal = $blendVersionIngredients->sum(function ($ingredient) {
            return $ingredient->pureAmount();
        });

        // Create collection to hold data for each perfume version
        $perfumeVersionBreakdowns = collect();

        foreach ($perfume->versions as $perfumeVersion) {

            // Pure total of essential oils (pure drops)
            $perfumePureTotal = ($perfumeVersion->concentration / 100) * $perfumeVersion->size;

            $perfumeVersionIngredients = $blendVersionIngredients->map(function ($ingredient) use (
                $blendVersionPureTotal,
                $perfumePureTotal,
                $perfumeVersion
            ) {
                // Percentage of this ingredient in the formula
                $purePercentage = $ingredient->purePercentage($blendVersionPureTotal);

                // Amount of this ingredient in ml
                $ingredientMl = ($purePercentage / 100) * $perfumePureTotal;

                // Convert ml to grams using bottle density
                $ingredientGrams = $ingredientMl * $ingredient->bottle->density;

                // Percentage of this ingredient in the final perfume
                $ingredientPercentage = ($ingredientMl / $perfumeVersion->size) * 100;

                return [
                    'material' => $ingredient->material->name,
                    'material_id' => $ingredient->material->id,
                    'variant' => $ingredient->variant(),
                    'percentage' => rtrim(rtrim(number_format($ingredientPercentage, 2, '.', ''), '0'), '.'),
                    'grams' => rtrim(rtrim(number_format($ingredientGrams, 3, '.', ''), '0'), '.'),
                ];
            });

            // Alcohol calculation
            $alcoholMl = $perfumeVersion->size - $perfumePureTotal;

            // Approximate perfumers alcohol density
            $alcoholDensity = 0.85;
            $alcoholGrams = $alcoholMl * $alcoholDensity;

            // Add alcohol row
            $perfumeVersionIngredients->push([
                'material' => 'Alcohol',
                'material_id' => null,
                'variant' => null,
                'percentage' => rtrim(rtrim(number_format((100 - $perfumeVersion->concentration), 2, '.', ''), '0'), '.'),
                'grams' => rtrim(rtrim(number_format($alcoholGrams, 3, '.', ''), '0'), '.'),
            ]);

            $perfumeVersionBreakdowns->push([
                'version' => $perfumeVersion,
                'ingredients' => $perfumeVersionIngredients,
            ]);
        }

        return view('perfumes.show', compact('perfume', 'perfumeVersionBreakdowns'));
    }

    public function update(UpdatePerfumeRequest $request, Perfume $perfume)
    {
        $validated = $request->validated();

        $perfume->update([
            'name' => $validated['name'],
        ]);

        // update timestamp
        $perfume->touch();

        return redirect()
            ->route('perfumes.index');
    }
}
