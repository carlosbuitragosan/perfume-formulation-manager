@php
   $base = 'block w-full md:inline-block md:w-auto md:min-w-[200px] text-left truncate px-2 py-1 rounded border text-sm font-medium transition hover:opacity-80';

   $variant = $attributes->get('variant', 'green');

   $colors = [
      'green' => 'bg-green-900 border-green-700 text-green-100',
      'blue' => 'bg-blue-900 border-blue-700 text-blue-100',
      'purple' => 'bg-purple-900 border-purple-700 text-purple-100',
   ];

   $hasBottle = ! empty($blendIngredient['bottle_id']);
@endphp

<a
   href="{{ route('materials.show', $blendIngredient['material_id']) }}"
   class="{{ $base }} {{ $colors[$variant] }} {{ ! $hasBottle ? 'opacity-60' : '' }}"
   data-ingredient-id="{{ $blendIngredient['blend_ingredient_id'] }}"
>
   {{ $blendIngredient['material_name'] }}
</a>
