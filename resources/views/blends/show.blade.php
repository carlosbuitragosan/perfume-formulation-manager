<x-app-layout>
   <x-slot name="header">
      <div id="header">
         <div class="mb-2">
            <a href="{{ route('blends.index') }}" class="text-xs">Blends</a>
         </div>
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

   <div class="p-6 space-y-6">
      @foreach ($blendVersions as $blendVersion)
         @php
            $blendIngredients = $blendVersion->formattedIngredients();
         @endphp

         <div
            id="version-{{ $blendVersion->id }}"
            data-testId="blend-version"
            data-version="{{ $blendVersion->version }}"
            tabindex="0"
            class="relative card px-3 py-3"
         >
            <div class="text-sm mb-3 pt-2">Version {{ $blendVersion->version }}</div>

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
               'blends.versions.partials.actions-dropdown', ['version' => $blendVersion]               )
            </div>

            {{-- Success message for version update --}}
            @if (session('success') && session('version_id') === $blendVersion->id)
               <x-flash type="success">{{ session('success') }}</x-flash>
            @endif

            {{-- Alert message for missing bottles or densities during perfume creation --}}
            @if (session('alerts') && session('version_id') === $blendVersion->id)
               <x-flash type="error">
                  <ul>
                     @foreach (session('alerts') as $alert)
                        <li>{{ $alert }}</li>
                     @endforeach
                  </ul>
               </x-flash>
            @endif
         </div>
      @endforeach
   </div>
</x-app-layout>
