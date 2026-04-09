@php
   $base = 'block w-full md:inline-block md:w-auto md:min-w-[200px] text-left truncate px-2 py-1 rounded text-sm font-medium transition hover:opacity-80';

   $variant = $variant ?? 'green';

   $colors = [
      'top' => 'bg-purple-600 text-white',
      'heart' => 'bg-green-700 text-white',
      'base' => 'bg-red-700 text-white',
      'top-heart' => 'bg-cyan-500 text-white',
      'heart-base' => 'bg-orange-500 text-white',
      'all' => 'bg-[#D4AF37] text-black',
   ];

   $colorClass = $colors[$variant] ?? $colors['green'];
   $hasBottle = ! empty($blendIngredient['bottle_id']);
@endphp

<a
   href="{{ route('materials.show', $blendIngredient['material_id']) }}?ingredient={{ $blendIngredient['blend_ingredient_id'] }}"
   data-ingredient-id="{{ $blendIngredient['blend_ingredient_id'] }}"
   class="group relative overflow-hidden {{ $base }} {{ $colorClass }} {{ ! $hasBottle ? 'opacity-60' : '' }}"
>
   <span class="relative z-10">
      {{ $blendIngredient['material_name'] }}
   </span>

   <span
      class="pointer-events-none absolute inset-0 rounded bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 ease-out"
   ></span>
</a>
