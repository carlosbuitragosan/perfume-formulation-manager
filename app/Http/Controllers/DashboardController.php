<?php

namespace App\Http\Controllers;

use App\Models\Blend;

class DashboardController extends Controller
{
    public function index()
    {
        $blends = Blend::where('user_id', auth()->id())
            ->latest('updated_at')
            ->get();

        return view('dashboard', compact('blends'));
    }
}
