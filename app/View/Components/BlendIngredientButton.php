<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BlendIngredientButton extends Component
{
    public $ingredient;

    public function __construct($ingredient)
    {
        $this->ingredient = $ingredient;
    }

    public function render(): View|Closure|string
    {
        return view('components.blend-ingredient-button');
    }
}
