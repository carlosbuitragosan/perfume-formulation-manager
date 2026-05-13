<?php

namespace App\Http\Controllers;

use App\Models\BlendIngredient;
use App\Models\Bottle;

class BlendIngredientController extends Controller
{
    public function assignBottle(BlendIngredient $blendIngredient, Bottle $bottle)
    {
        $blend = $blendIngredient->blendVersion->blend;

        // Authorize user from app/Policies/BlendPolicy
        $this->authorize('update', $blend);

        // See Models/BlendIngredient for logic
        $isBottleAssigned = $blendIngredient->assignBottle($bottle);

        if (! $isBottleAssigned) {
            return redirect()
                ->back()
                ->with('error', 'Cannot assign a finished bottle')
                ->with('bottle_id', $bottle->id);
        }

        return redirect()->route('blends.show', $blend)
            ->with('success', "Bottle assigned to {$bottle->material->name}")
            ->with('version_id', $blendIngredient->blendVersion->id);
    }
}
