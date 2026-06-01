<x-app-layout>
   <x-slot name="header">
      <div id="header">
         <h2 class="font-semibold text-xl mr-2">{{ $perfume->name }}</h2>
         <span class="text-sm text-gray-500">
            {{ $perfume->size }} ml - {{ $perfume->concentration }}% Concentration
         </span>
      </div>
   </x-slot>

   <div class="p-4 space-y-4">
      <div
         id="perfume-{{ $perfume->id }}"
         data-testId="perfume"
         data-perfume="{{ $perfume->name }}"
         tabindex="0"
         class="relative card px-3 py-3"
      >
         <div class="overflow-x-auto">
            <table class="w-full text-sm border-separate border-spacing-x-3">
               <thead>
                  <tr class="text-left">
                     <th class="py-2">Ingredient</th>
                     <th class="py-2">% in Perfume</th>
                     <th class="py-2">Weight</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($perfumeIngredients as $perfumeIngredient)
                     <tr data-material-id="{{ $perfumeIngredient['material_id'] ?? 'Alcohol' }}">
                        <td data-col="material" class="py-2">
                           <x-perfume-ingredient-badge
                              :material="$perfumeIngredient['material']"
                              :variant="$perfumeIngredient['variant']"
                           />
                        </td>
                        <td data-col="percentage" class="py-2">
                           {{ $perfumeIngredient['percentage'] }}%
                        </td>
                        <td data-col="weight" class="py-2">{{ $perfumeIngredient['grams'] }} g</td>
                     </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
      </div>
   </div>
</x-app-layout>
