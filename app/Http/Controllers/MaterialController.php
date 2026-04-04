<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\BlendIngredient;
use App\Models\Material;
use Illuminate\Database\QueryException;

class MaterialController extends Controller
{
    public function index()
    {
        return view('materials.index');
    }

    // Create form
    public function create()
    {
        return view('materials.create');
    }

    // Store
    public function store(StoreMaterialRequest $request)
    {
        // validate request
        $data = $request->validated();

        // assign user ID to material
        $data['user_id'] = auth()->id();

        // Create material
        $material = Material::create($data);

        return redirect()
            ->route('materials.index')
            ->withFragment('#material-'.$material->id)
            ->with('success', "{$material->name} added")
            ->with('material_id', $material->id);
    }

    // Edit form
    public function edit(Material $material)
    {
        return view('materials.edit', compact('material'));
    }

    // Update
    public function update(UpdateMaterialRequest $request, Material $material)
    {
        $data = $request->validated();

        $material->update($data);

        return redirect()
            ->route('materials.index')
            ->withFragment('#material-'.$material->id)
            ->with('success', "{$material->name} updated")
            ->with('material_id', $material->id);
    }

    // material show page
    public function show(Material $material)
    {
        // GET request, data is in url
        $blendIngredientId = request()->integer('ingredient');
        $selectedBottleId = null;
        $blendIngredient = null;

        if ($blendIngredientId) {
            $blendIngredient = BlendIngredient::findOrFail($blendIngredientId);
            $selectedBottleId = $blendIngredient->bottle_id;
        }

        $bottles = $material->bottlesFor($blendIngredient);

        return view('materials.show', compact(
            'material',
            'selectedBottleId',
            'blendIngredient',
            'bottles'
        ));
    }

    public function destroy(Material $material)
    {
        try {
            $material->delete();

            return redirect()
                ->route('materials.index')
                ->with('success', "{$material->name} deleted");
        } catch (QueryException $e) {
            return redirect()
                ->route('materials.edit', $material)
                ->with('error', "{$material->name} is in use and cannot be deleted");
        }

    }
}
