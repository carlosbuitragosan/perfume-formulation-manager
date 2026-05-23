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
}
