<x-app-layout>
   <x-slot name="header">
      <h2 class="font-semibold text-xl">Create Blend</h2>
   </x-slot>

   <div class="p-4">
      <form
         id="create-blend-form"
         method="POST"
         action="{{ route('blends.store') }}"
         class="space-y-3"
      >
         @csrf
         <label class="block">
            <span class="text-sm">Blend Name</span>
            <input type="text" name="name" value="{{ old('name') }}" class="p-2 w-full" />
         </label>
         @error('name')
            <div data-testid="error-name">
               <x-flash type="error" class="">{{ $message }}</x-flash>
            </div>
         @enderror

         <h3 class="font-medium">Ingredients</h3>
         @error('materials')
            <div>
               <x-flash type="error">{{ $message }}</x-flash>
            </div>
         @enderror

         @php
            $oldMaterials = old('materials');
            $rows =
               is_array($oldMaterials) && count($oldMaterials)
                  ? $oldMaterials
                  : [
                     ['material_id' => '', 'drops' => '', 'dilution' => 25],
                     ['material_id' => '', 'drops' => '', 'dilution' => 25],
                  ];
         @endphp

         {{-- INGREDIENTS --}}
         <div class="space-y-6" data-testid="ingredients-container">
            @foreach ($rows as $index => $row)
               <div
                  class="flex flex-col gap-3"
                  data-testid="ingredient-row"
                  data-index="{{ $index }}"
               >
                  {{-- MATERIAL --}}
                  <select name="materials[{{ $index }}][material_id]" class="w-full p-2">
                     <option value="">Select material</option>
                     @foreach ($materials as $material)
                        <option
                           value="{{ $material->id }}"
                           @selected((string) ($row['material_id'] ?? '') === (string) $material->id)
                        >
                           {{ $material->name }}
                        </option>
                     @endforeach
                  </select>
                  @error("materials.$index.material_id")
                     <div data-testid="error-materials.{{ $index }}.material_id">
                        <x-flash type="error">{{ $message }}</x-flash>
                     </div>
                  @enderror

                  <div class="flex gap-4">
                     {{-- DROPS --}}
                     <input
                        type="text"
                        inputmode="numeric"
                        name="materials[{{ $index }}][drops]"
                        placeholder="number of drops"
                        class="w-full p-2"
                        value="{{ $row['drops'] ?? '' }}"
                     />

                     {{-- DILUTION --}}
                     <select name="materials[{{ $index }}][dilution]" class="w-full p-2">
                        @foreach ([25, 10, 1] as $dilution)
                           <option
                              value="{{ $dilution }}"
                              @selected((int) ($row['dilution'] ?? 25) === $dilution)
                           >
                              {{ $dilution }}%
                           </option>
                        @endforeach
                     </select>
                  </div>
                  @error("materials.$index.drops")
                     <div data-testid="error-materials.{{ $index }}.drops">
                        <x-flash type="error">{{ $message }}</x-flash>
                     </div>
                  @enderror
               </div>
            @endforeach
         </div>

         <x-primary-button type="button" data-testid="add-ingredient" class="bg-indigo-600">
            Add ingredient
         </x-primary-button>

         {{-- template for dynamic rows --}}
         <template data-testid="ingredient-template">
            <div
               class="flex flex-col gap-3"
               data-testid="ingredient-template-row"
               data-index="__INDEX__"
            >
               {{-- MATERIAL --}}
               <select name="materials[__INDEX__][material_id]" class="w-full p-2">
                  <option value="">Select material</option>
                  @foreach ($materials as $material)
                     <option value="{{ $material->id }}">{{ $material->name }}</option>
                  @endforeach
               </select>
               <div class="flex gap-4">
                  {{-- DROPS --}}
                  <input
                     type="text"
                     inputmode="numeric"
                     name="materials[__INDEX__][drops]"
                     placeholder="number of drops"
                     class="w-full p-2"
                  />
                  {{-- DILUTION --}}
                  <select name="materials[__INDEX__][dilution]" class="w-full p-2">
                     <option value="25">25%</option>
                     <option value="10">10%</option>
                     <option value="1">1%</option>
                  </select>
               </div>
            </div>
         </template>

         <div class="flex gap-2">
            <x-primary-button type="submit" class="bg-green-600 hover:bg-green-700">
               SAVE
            </x-primary-button>
            <x-cancel-link href="{{ route('blends.index') }}">CANCEL</x-cancel-link>
         </div>
      </form>
   </div>
</x-app-layout>
