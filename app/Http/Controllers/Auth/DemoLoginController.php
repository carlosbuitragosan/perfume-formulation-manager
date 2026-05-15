<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (! config('demo.mode')) {
            abort(404);
        }

        $email = config('demo.user_email');
        $demo = User::where('email', $email)->first();

        abort_if(! $demo, 404);

        Auth::login($demo);
        $request->session()->regenerate();

        return redirect()->intended(route('blends.index'));
    }
}
