<?php

namespace App\Http\Controllers;

use App\Models\Blend;
use App\Models\Material;

class BlendVersionController extends Controller
{
    public function create(Blend $blend)
    {
        // Get a collection of all materials for user
        $materials = Material::forUserOrdered(auth()->id())->get();

        return view('blends.versions.create', compact('materials', 'blend'));
    }
}
