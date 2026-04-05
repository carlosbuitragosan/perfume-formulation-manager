<?php

namespace App\Http\Controllers;

use App\Http\Requests\Material\StoreMaterialRequest;
use App\Http\Requests\Material\UpdateMaterialRequest;
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
        $this->authorize('update', $material);

        return view('materials.edit', compact('material'));
    }

    // Update
    public function update(UpdateMaterialRequest $request, Material $material)
    {
        $this->authorize('update', $material);

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
        $this->authorize('view', $material);

        // GET request, data is in url
        $blendIngredientId = request()->integer('ingredient');
        $selectedBottleId = null;
        $blendIngredient = null;

        if ($blendIngredientId) {
            $blendIngredient = BlendIngredient::findOrFail($blendIngredientId);

            $this->authorize('view', $blendIngredient->blendVersion->blend);

            $selectedBottleId = $blendIngredient->bottle_id;
        }

        // Sort bottles for display @ Models/Material
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
        $this->authorize('delete', $material);

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
