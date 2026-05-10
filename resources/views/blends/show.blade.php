<x-app-layout>
   <x-slot name="header">
      <div class="flex items-center justify-between">
         <h2 class="font-semibold text-xl mr-2">{{ $blend->name }}</h2>

         <div>
            <x-link href="{{ route('blends.versions.create', $blend) }}">New Version</x-link>
         </div>
      </div>
   </x-slot>

   <div class="p-4 space-y-4">
      @foreach ($versions as $version)
         @php
            $blendIngredients = $version->formattedIngredients();
         @endphp

         <div data-testId="blend-version" data-version="{{ $version->version }}" class="card p-4">
            <div class="font-semibold mb-3 pt-2">{{ $version->version }}</div>

            <div class="overflow-x-auto">
               <table class="w-full text-sm border-separate border-spacing-x-3">
                  <thead>
                     <tr class="text-left">
                        <th class="py-2">Ingredient</th>
                        <th class="py-2">Drops</th>
                        <th class="py-2">Dilution</th>
                        <th class="py-2">Pure %</th>
                     </tr>
                  </thead>
                  <tbody>
                     @foreach ($blendIngredients as $blendIngredient)
                        <tr data-ingredient-id="{{ $blendIngredient['blend_ingredient_id'] }}">
                           <td data-col="material" class="py-2">
                              <x-blend-ingredient-link
                                 :blendIngredient="$blendIngredient"
                                 :variant="$blendIngredient['variant']"
                              />
                           </td>
                           <td data-col="drops" class="py-2">
                              {{ $blendIngredient['drops'] }}
                           </td>
                           <td data-col="dilution" class="py-2">
                              {{ $blendIngredient['dilution'] }}
                           </td>
                           <td data-col="pure_pct" class="py-2">
                              {{ $blendIngredient['pure_pct'] }}
                           </td>
                        </tr>
                     @endforeach
                  </tbody>
               </table>
            </div>

            <div class="flex gap-2">
               <x-link href="{{ route('blends.edit', $blend) }}">EDIT</x-link>

               <form
                  method="POST"
                  action=""
                  onsubmit="return confirm('Delete {{ $blend->name }}?');"
               >
                  @csrf
                  @method('DELETE')
                  <x-danger-button>DELETE</x-danger-button>
               </form>
            </div>

            @if (session('success'))
               <x-flash type="success">{{ session('success') }}</x-flash>
            @endif
         </div>
      @endforeach
   </div>
</x-app-layout>
