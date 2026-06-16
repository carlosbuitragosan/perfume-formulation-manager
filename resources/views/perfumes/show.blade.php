<x-app-layout>
   <x-slot name="header">
      <div id="header">
         <div class="mb-4">
            <a href="{{ route('perfumes.index') }}" class="text-xs">Perfumes</a>
         </div>
         <h2 class="font-semibold text-xl mr-2">{{ $perfume->name }}</h2>
         <div>
            <a
               data-testid="source-blend-link"
               class="text-xs"
               href="{{ route('blends.show', $perfume->blendVersion->blend) . '#version-' . $perfume->blendVersion->id }}"
            >
               Blend: {{ $perfume->blendVersion->blend->name }}, Version
               {{ $perfume->blendVersion->version }}
            </a>
         </div>
      </div>
   </x-slot>

   <div class="p-6 space-y-6">
      @foreach ($perfumeVersionBreakdowns as $breakdown)
         @php
            $perfumeVersion = $breakdown['version'];
            $perfumeVersionIngredients = $breakdown['ingredients'];
         @endphp

         <div
            id="version-{{ $perfumeVersion->id }}"
            data-testId="perfume"
            data-perfume="{{ $perfume->name }}"
            tabindex="0"
            class="relative card px-3 py-3"
         >
            <span class="text-sm px-3">
               {{ $perfumeVersion->size }} ml - {{ $perfumeVersion->concentration }}%
               Concentration
            </span>

            <div class="overflow-x-auto">
               <table class="w-full text-sm border-separate border-spacing-x-3">
                  <thead>
                     <tr class="text-left">
                        <th class="py-2">Ingredient</th>
                        <th class="py-2 whitespace-nowrap">% in Perfume</th>
                        <th class="py-2 whitespace-nowrap">Weight</th>
                     </tr>
                  </thead>
                  <tbody>
                     @foreach ($perfumeVersionIngredients as $perfumeVersionIngredient)
                        <tr
                           data-material-id="{{ $perfumeVersionIngredient['material_id'] ?? 'Alcohol' }}"
                        >
                           <td data-col="material" class="py-2">
                              <x-perfume-ingredient-badge
                                 :material="$perfumeVersionIngredient['material']"
                                 :variant="$perfumeVersionIngredient['variant']"
                              />
                           </td>
                           <td data-col="percentage" class="py-2 whitespace-nowrap">
                              {{ $perfumeVersionIngredient['percentage'] }}%
                           </td>
                           <td data-col="weight" class="py-2 whitespace-nowrap">
                              {{ $perfumeVersionIngredient['grams'] }} g
                           </td>
                        </tr>
                     @endforeach
                  </tbody>
               </table>
            </div>

            {{-- Show success message for created version --}}
            @if (session('success') && session('version_id') === $perfumeVersion->id)
               <x-flash type="success">{{ session('success') }}</x-flash>
            @endif
         </div>
      @endforeach
   </div>
</x-app-layout>
