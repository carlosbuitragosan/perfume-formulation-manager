<?php

namespace App\Http\Controllers;

use App\Http\Requests\Blend\StoreBlendRequest;
use App\Http\Requests\Blend\UpdateBlendRequest;
use App\Models\Blend;
use App\Models\Material;

class BlendController extends Controller
{
    public function create()
    {
        // Get a collection of all materials for user
        $materials = Material::forUserOrdered(auth()->id())->get();

        return view('blends.create', compact('materials'));
    }

    // Store
    public function store(StoreBlendRequest $request)
    {
        // validate
        $data = $request->validated();

        // Create blend + version
        $blend = Blend::createBlendWithIngredients(
            $data,
            $request->user()->id,
        );

        return redirect()->route('blends.show', $blend)
            ->with('success', "{$blend->name} added")
            ->with('blend_id', $blend->id);
    }

    public function show(Blend $blend)
    {
        $this->authorize('view', $blend);

        // Return version or null
        $versions = $blend->versions()
            ->with(['ingredients.material'])
            ->get();

        // Return ingredients for display: drops, dilution, pure percetates, labels, etc
        // $blendIngredients = $blend->formattedIngredients($version);

        return view('blends.show', compact('blend', 'versions'));
    }

    public function destroy(Blend $blend)
    {
        $this->authorize('delete', $blend);

        $blend->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', "{$blend->name} deleted")
            ->with('blend_id', $blend->id);
    }

    public function edit(Blend $blend)
    {
        $this->authorize('update', $blend);

        // Must return a version else 404
        $version = $blend->currentVersionOrFail();

        // Get all materials for user
        $materials = Material::forUserOrdered(auth()->id())->get();

        return view('blends.edit', compact('blend', 'version', 'materials'));
    }

    public function update(UpdateBlendRequest $request, Blend $blend)
    {
        $this->authorize('update', $blend);

        $data = $request->validated();

        $blend->updateName($data['name']);

        // Get blend version
        $version = $blend->currentVersionOrFail();

        // Rebuild ingredients and assign bottles where possible
        $blend->rebuildIngredients($request->user(), $version, $data['materials']);

        // Touch blend to update updated_at timestamp for sorting on UI
        $blend->touch();

        return redirect()->route('blends.show', $blend)
            ->with('success', "{$blend->name} updated")
            ->with('blend_id', $blend->id);
    }
}
