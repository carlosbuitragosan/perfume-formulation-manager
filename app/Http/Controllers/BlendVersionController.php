<?php

namespace App\Http\Controllers;

use App\Http\Requests\Blend\StoreBlendVersionRequest;
use App\Http\Requests\Blend\UpdateBlendVersionRequest;
use App\Models\Blend;
use App\Models\BlendVersion;
use App\Models\Material;
use Illuminate\Http\Request;

class BlendVersionController extends Controller
{
    public function create(Request $request, Blend $blend)
    {
        // Get a collection of all materials for user
        $materials = Material::forUserOrdered(auth()->id())->get();

        // pass ingredients of the version to copy from
        $sourceVersion = null;
        if ($request->filled('from')) {
            $sourceVersion = $blend->versionToClone($request->from);
        }

        return view('blends.versions.create', compact('materials', 'blend', 'sourceVersion'));
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

    // Edit form
    public function edit(Blend $blend, BlendVersion $version)
    {
        $this->authorize('update', $version);

        // Get a collection of all materials for user
        $materials = Material::forUserOrdered(auth()->id())->get();

        return view('blends.versions.edit', compact('blend', 'version', 'materials'));
    }

    public function update(UpdateBlendVersionRequest $request, Blend $blend, BlendVersion $version)
    {
        // Autorize user
        $this->authorize('update', $version);

        // validate
        $data = $request->validated();

        // Update version
        $version->rebuildIngredients($request->user(), $data['materials']);

        return redirect()
            ->route('blends.show', $blend)
            ->withFragment('version-'.$version->id)
            ->with('success', "Version {$version->version} updated")
            ->with('version_id', $version->id);
    }
}
