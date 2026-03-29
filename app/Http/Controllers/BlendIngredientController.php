<?php

namespace App\Http\Controllers;

use App\Models\BlendIngredient;
use App\Models\Bottle;
use Illuminate\Http\Request;

class BlendIngredientController extends Controller
{
    public function assignBottle(Request $request, BlendIngredient $blendIngredient)
    {
        $blend = $blendIngredient->blendVersion->blend;

        abort_unless($blend->user_id === auth()->id(), 404);

        // Validate request
        $data = $request->validate([
            'bottle_id' => ['required', 'integer', 'exists:bottles,id'],
        ]);

        // Assign bottle to ingredient
        $bottle = Bottle::findOrFail($data['bottle_id']);
        if (
            ! $blendIngredient->bottle_id &&
        $blendIngredient->material_id === $bottle->material_id
        ) {
            $blendIngredient->update([
                'bottle_id' => $data['bottle_id'],
            ]);
        }

        // return redirect
        return redirect()->route('blends.show', $blend)
            ->with('ok', "Bottle assigned to {$bottle->material->name}");
    }
}
