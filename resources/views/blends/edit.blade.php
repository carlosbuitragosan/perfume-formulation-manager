<x-app-layout>
   <x-slot name="header">
      <h2 class="font-semibold text-xl">Edit Blend</h2>
      <span>{{ $blend->name }}</span>
   </x-slot>

   <div class="p-4">
      <form method="POST" action="#" class="space-y-3">
         @csrf
         @method('PUT')

         <label class="block">
            <span class="text-sm">Blend Name</span>
            <input
               type="text"
               name="name"
               value="{{ old('name', $blend->name) }}"
               class="p-2 w-full"
            />
         </label>

         <h3 class="font-medium">Ingredients</h3>

         {{-- INGREDIENTS --}}
         <div class="space-y-6" data-testid="ingredients-container">
            @foreach ($version->ingredients as $index => $ingredient)
               <div
                  class="flex flex-col gap-3"
                  data-testid="ingredient-row"
                  data-index="{{ $index }}"
               >
                  {{-- MATERIALS --}}
                  <select name="materials[{{ $index }}][material_id]" class="w-full p-2">
                     <option value="">Select material</option>
                     @foreach ($materials as $material)
                        <option
                           value="{{ $material->id }}"
                           @selected((int) old("materials.$index.material_id", $ingredient->material_id) === (int) $material->id)
                        >
                           {{ $material->name }}
                        </option>
                     @endforeach
                  </select>

                  {{-- DROPS --}}
                  <div class="flex gap-4">
                     <input
                        type="text"
                        inputmode="numeric"
                        name="materials[{{ $index }}][drops]"
                        class="w-full p-2"
                        value="{{ old("materials.$index.drops", $ingredient->drops) }}"
                     />
                     <select name="materials[{{ $index }}][dilution]" class="w-full p-2">
                        @foreach ([25, 10, 1] as $dilution)
                           <option
                              value="{{ $dilution }}"
                              @selected((int) old("materials.$index.dilution", $ingredient->dilution) === (int) $dilution)
                           >
                              {{ $dilution }}
                           </option>
                        @endforeach
                     </select>
                  </div>
               </div>
            @endforeach
         </div>

         <div class="flex gap-2">
            <x-primary-button type="submit" class="bg-green-600 hover:bg-green-700">
               SAVE
            </x-primary-button>
            <x-cancel-link href="{{ route('dashboard') }}">CANCEL</x-cancel-link>
         </div>
      </form>
   </div>
</x-app-layout>
