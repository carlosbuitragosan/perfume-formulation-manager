<?php

namespace App\Http\Controllers;

use App\Models\Blend;
use App\Models\Bottle;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BlendController extends Controller
{
    public function create()
    {
        $materials = Material::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('blends.create', compact('materials'));
    }

    public function store(Request $request)
    {
        // clean up incoming
        // materials array:
        // remove empty rows
        // reindex - values()
        // convert to plain PHP array - all()
        $materials = collect($request->input('materials', []))
            ->filter(function ($row) {
                $row = is_array($row) ? $row : [];
                $hasMaterial = ! empty($row['material_id']);
                $hasDrops = isset($row['drops']) && $row['drops'] !== '';

                return $hasMaterial || $hasDrops;
            })
            ->values()
            ->all();

        // Replace original request materials with cleaned version
        $request->merge(['materials' => $materials]);

        // validate
        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['required', 'string', 'max:255'],
                'materials' => ['required', 'array', 'min:2'],
                'materials.*.material_id' => [
                    'required',
                    'integer',
                    Rule::exists('materials', 'id')
                        ->where(fn ($q) => $q->where('user_id', auth()->id())),
                ],
                'materials.*.drops' => ['required', 'integer', 'min:1', 'max:999'],
                'materials.*.dilution' => ['required', 'integer', 'in:25,10,1'],
            ],
            // custom error messages
            [
                'name.required' => 'Enter a blend name',
                'materials.required' => 'Add at least two ingredients',
                'materials.min' => 'Add at least two ingredients',
                'materials.*.material_id.required' => 'Select a material',
                'materials.*.drops.required' => 'Enter the number of drops',
                'materials.*.drops.integer' => 'Drops must be a whole number',
                'materials.*.drops.max' => 'Drops cannot exceed 999',
            ]
        );

        // extra validation to prevent duplicate materials
        $validator->after(function ($validator) use ($request) {
            $materials = $request->input('materials', []);

            $ids = collect($materials)
                ->pluck('material_id')
                ->filter(); // remove nulls

            if ($ids->count() !== $ids->unique()->count()) {
                $validator->errors()->add('materials', 'You can\'t use the same material twice.');
            }
        });

        // run the validator
        $data = $validator->validate();

        // Create blend
        $blend = Blend::create([
            'user_id' => auth()->id(),
            'name' => trim($data['name']),
        ]);

        // Create first version
        $version = $blend->versions()->create([
            'version' => '1.0',
        ]);

        // Create ingredients for this version
        foreach ($data['materials'] as $row) {
            // Find available bottles for each material
            $availableBottlesByMaterial = Bottle::where('user_id', auth()->id())
                ->where('material_id', $row['material_id'])
                ->where('is_finished', 0)
                ->get();

            $bottleId = null;

            // Assign bottle if only one exists
            if ($availableBottlesByMaterial->count() === 1) {
                $bottleId = $availableBottlesByMaterial->first()->id;
            }

            $version->ingredients()->create([
                'material_id' => $row['material_id'],
                'bottle_id' => $bottleId,
                'drops' => $row['drops'],
                'dilution' => $row['dilution'],
            ]);
        }

        return redirect()->route('blends.show', $blend);
    }

    public function show(Blend $blend)
    {
        abort_unless($blend->user_id === auth()->id(), 404);

        $version = $blend->versions()
            ->where('version', '1.0')
            ->with(['ingredients.material'])
            ->first();

        $blendIngredients = collect();

        if ($version) {
            $pureTotal = $version->ingredients->sum(function ($ing) {
                return $ing->drops * ($ing->dilution / 100);
            });

            $blendIngredients = $version->ingredients->map(function ($ing) use ($pureTotal) {
                $pure = $ing->drops * ($ing->dilution / 100);
                $pct = $pureTotal > 0 ? ($pure / $pureTotal) * 100 : 0;

                return [
                    'blend_ingredient_id' => $ing->id,
                    'material_id' => $ing->material_id,
                    'material_name' => $ing->material->name,
                    'bottle_id' => $ing->bottle_id,
                    'drops' => (string) $ing->drops,
                    'dilution' => $ing->dilution.'%',
                    'pure_pct' => number_format($pct, 2).'%',
                ];
            });
        }

        return view('blends.show', compact('blend', 'version', 'blendIngredients'));
    }

    public function destroy(Blend $blend)
    {
        abort_unless($blend->user_id === auth()->id(), 404);

        $blend->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', "{$blend->name} deleted")
            ->with('blend_id', $blend->id);
    }

    public function edit(Blend $blend)
    {
        abort_unless($blend->user_id === auth()->id(), 404);

        $version = $blend->versions()
            ->where('version', '1.0')
            ->with(['ingredients.material'])
            ->firstOrFail();

        $materials = Material::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('blends.edit', compact('blend', 'version', 'materials'));
    }

    public function update(Request $request, Blend $blend)
    {
        abort_unless($blend->user_id === auth()->id(), 404);
        // dd($request->input('materials'));
        $materials = collect($request->input('materials', []))
            ->filter(function ($row) {
                $row = is_array($row) ? $row : [];
                $hasMaterial = ! empty($row['material_id']);
                $hasDrops = isset($row['drops']) && $row['drops'] !== '';

                return $hasMaterial || $hasDrops;
            })
            ->values()
            ->all();

        $request->merge(['materials' => $materials]);

        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['required', 'string', 'max:255'],
                'materials' => ['required', 'array', 'min:2'],
                'materials.*.material_id' => [
                    'required',
                    'integer',
                    Rule::exists('materials', 'id')
                        ->where(fn ($q) => $q->where('user_id', auth()->id())),
                ],
                'materials.*.drops' => ['required', 'integer', 'min:1', 'max:999'],
                'materials.*.dilution' => ['required', 'integer', 'in:25,10,1'],
            ],
            [
                'name.required' => 'Enter a blend name',
                'materials.required' => 'Add at least two ingredients',
                'materials.min' => 'Add at least two ingredients',
                'materials.*.material_id.required' => 'Select a material',
                'materials.*.drops.required' => 'Enter the number of drops',
                'materials.*.drops.integer' => 'Drops must be a whole number',
                'materials.*.drops.max' => 'Drops cannot exceed 999',
            ]
        );

        $validator->after(function ($validator) use ($request) {
            $materials = $request->input('materials', []);

            $ids = collect($materials)
                ->pluck('material_id')
                ->filter();

            if ($ids->count() !== $ids->unique()->count()) {
                $validator->errors()->add('materials', 'You can\'t use the same material twice.');
            }
        });

        $data = $validator->validate();

        // Update blend name
        $blend->update([
            'name' => trim($data['name']),
        ]);

        // Get blend version
        $version = $blend->versions()
            ->where('version', '1.0')
            ->firstOrFail();

        // Preserve bottle assignments (materialId => bottleId) from DB
        $existingBottleIds = $version->ingredients
            ->pluck('bottle_id', 'material_id');

        // Delete ingredients
        $version->ingredients()->delete();

        // Get the materials from the request
        $incomingMaterialIds = collect($data['materials'])
            ->pluck('material_id');

        // Find all available bottles for all materials in the request (materialId => collection of bottles)
        $availableBottlesByMaterial = Bottle::where('user_id', auth()->id())
            ->where('is_finished', 0)
            ->WhereIn('material_id', $incomingMaterialIds)
            ->get()
            ->groupBy('material_id');

        foreach ($data['materials'] as $row) {
            $materialId = $row['material_id'];
            $bottleId = null;

            // If ingredient being added already existed in the blend...
            if ($existingBottleIds->has($materialId)) {
                // ... fetch it from the list and assign a bottle id
                $bottleId = $existingBottleIds[$materialId];
            } else {
                // Fetch available bottles from newly added ingredient
                $materialBottles = $availableBottlesByMaterial->get($materialId, collect());
                if ($materialBottles->count() === 1) {
                    // Assign the bottle ID
                    $bottleId = $materialBottles->first()->id;
                }
            }
            // Create a new ingredient from existing ones and newly added
            $version->ingredients()->create([
                'material_id' => $materialId,
                'bottle_id' => $bottleId,
                'drops' => $row['drops'],
                'dilution' => $row['dilution'],
            ]);
        }

        return redirect()->route('blends.show', $blend);
    }
}
