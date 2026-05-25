<?php

namespace App\Http\Controllers;

use App\Models\BlendVersion;
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

    public function show() {}
}
