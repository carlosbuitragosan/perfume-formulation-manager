<x-app-layout>
   <x-slot name="header">
      <div class="flex items-center justify-between">
         <h2 class="font-semibold text-xl mr-2">{{ $blend->name }}</h2>
      </div>
   </x-slot>

   {{-- Succes delete version --}}
   @if (session('success') && ! session('version_id'))
      <x-flash type="success">{{ session('success') }}</x-flash>
   @endif

   @if (session('error'))
      <x-flash type="error">{{ session('error') }}</x-flash>
   @endif

   <div class="p-4 space-y-4">
      @foreach ($versions as $version)
         @php
            $blendIngredients = $version->formattedIngredients();
         @endphp

         <div
            id="version-{{ $version->id }}"
            data-testId="blend-version"
            data-version="{{ $version->version }}"
            tabindex="0"
            class="relative card px-3 py-3"
         >
            <div class="font-semibold mb-3 pt-2">Version {{ $version->version }}</div>

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
                        <tr data-material-id="{{ $blendIngredient['material_id'] }}">
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

            {{-- Dropdown --}}
            <div class="absolute top-2 right-2">
               @include(
               'blends.versions.partials.actions-dropdown', ['version' => $version]               )
            </div>

            {{-- Success message for version update --}}
            @if (session('success') && session('version_id') === $version->id)
               <x-flash type="success">{{ session('success') }}</x-flash>
            @endif
         </div>
      @endforeach
   </div>
</x-app-layout>
