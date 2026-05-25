<?php

namespace App\Http\Controllers;

use App\Models\BlendVersion;
use Illuminate\Http\Request;

class PerfumeController extends Controller
{
    public function create(Request $request, BlendVersion $version)
    {

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
