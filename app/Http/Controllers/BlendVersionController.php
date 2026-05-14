<?php

namespace App\Http\Controllers;

use App\Http\Requests\Blend\StoreBlendVersionRequest;
use App\Http\Requests\Blend\UpdateBlendVersionRequest;
use App\Models\Blend;
use App\Models\BlendVersion;
use App\Models\Material;

class BlendVersionController extends Controller
{
    public function create(Blend $blend)
    {
        // Get a collection of all materials for user
        $materials = Material::forUserOrdered(auth()->id())->get();

        return view('blends.versions.create', compact('materials', 'blend'));
    }

    public function store(Blend $blend, StoreBlendVersionRequest $request)
    {
        // validate
        $data = $request->validated();

        // Create version
        $version = $blend->createVersionWithIngredients($data);

        return redirect()->route('blends.show', $blend)
            ->with('success', "Version {$version->version} added")
            ->with('version_id', $version->id);
    }

    public function update(UpdateBlendVersionRequest $request, Blend $blend, BlendVersion $version)
    {
        // validate
        $data = $request->validated();

        // Update version
        $version->rebuildIngredients($request->user(), $data['materials']);

        return redirect()->route('blends.show', $blend)
            ->with('success', "Version {$version->version} updated")
            ->with('version_id', $version->id);
    }
}
