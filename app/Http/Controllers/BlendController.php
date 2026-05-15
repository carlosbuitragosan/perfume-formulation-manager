<?php

namespace App\Http\Controllers;

use App\Http\Requests\Blend\StoreBlendRequest;
use App\Http\Requests\Blend\UpdateBlendRequest;
use App\Models\Blend;
use App\Models\Material;

class BlendController extends Controller
{
    public function index()
    {
        $blends = Blend::where('user_id', auth()->id())
            ->latest('updated_at')
            ->get();

        return view('blends.index', compact('blends'));
    }

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

        $version = $blend->versions()->latest()->first();

        return redirect()->route('blends.show', $blend)
            ->with('success', "Version {$version->version} added")
            ->with('version_id', $version->id);
    }

    public function show(Blend $blend)
    {
        $this->authorize('view', $blend);

        // Return version or null
        $versions = $blend->versions()
            ->with(['ingredients.material'])
            ->get();

        return view('blends.show', compact('blend', 'versions'));
    }

    public function destroy(Blend $blend)
    {
        $this->authorize('delete', $blend);

        $blend->delete();

        return redirect()
            ->route('blends.index')
            ->with('success', "{$blend->name} deleted")
            ->with('blend_id', $blend->id);
    }

    public function update(UpdateBlendRequest $request, Blend $blend)
    {
        $this->authorize('update', $blend);

        $data = $request->validated();

        $blend->update([
            'name' => $data['name'],
        ]);

        // Touch blend to update updated_at timestamp for sorting on UI
        $blend->touch();

        return redirect()->route('blends.show', $blend);
    }
}
