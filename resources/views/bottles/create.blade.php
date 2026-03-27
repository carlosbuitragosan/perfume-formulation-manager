<x-app-layout>
   <x-slot name="header">
      <h2 class="font-semibold text-xl">Create Bottle</h2>
      <span class="text-sm">{{ $material->name }}</span>
   </x-slot>

   <div class="p-4">
      <form
         method="POST"
         action="{{ route('materials.bottles.store', $material) }}"
         enctype="multipart/form-data"
         class="space-y-3"
      >
         @csrf
         <!-- Pass blend ingredient context so newly created bottles can be assigned -->
         <input type="hidden" name="ingredient" value="{{ request('ingredient') }}" />

         <label class="block">
            <span class="text-sm">Supplier name</span>
            <input
               type="text"
               name="supplier_name"
               class="p-2 w-full"
               value="{{ old('suplier_name') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Supplier URL</span>
            <input
               type="url"
               name="supplier_url"
               class="p-2 w-full"
               value="{{ old('supplier_url') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Batch code</span>
            <input
               type="text"
               name="batch_code"
               class="p-2 w-full"
               value="{{ old('batch_code') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Method</span>
            <select name="method" class="p-2 w-full" required>
               <option value="" disabled {{ old('method') ? '' : 'selected' }}>
                  Chose method...
               </option>
               @foreach (\App\Enums\ExtractionMethod::cases() as $m)
                  <option value="{{ $m->value }}" @selected(old('method') === $m->value)>
                     {{ $m->label() }}
                  </option>
               @endforeach
            </select>
         </label>

         <label class="block">
            <span class="text-sm">Plant part</span>
            <input
               type="text"
               name="plant_part"
               class="p-2 w-full"
               value="{{ old('plant_part') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Origin country</span>
            <input
               type="text"
               name="origin_country"
               class="p-2 w-full"
               value="{{ old('origin_country') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Purchase date</span>
            <input
               type="date"
               name="purchase_date"
               class="p-2 w-full"
               value="{{ old('purchase_date') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Expiry date</span>
            <input
               type="date"
               name="expiry_date"
               class="p-2 w-full"
               value="{{ old('expiry_date') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Volume (ml)</span>
            <input
               type="number"
               name="volume_ml"
               inputmode="numeric"
               class="p-2 w-full"
               value="{{ old('volume_ml') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Density (g/ml)</span>
            <input
               type="number"
               name="density"
               inputmode="decimal"
               step="0.001"
               class="p-2 w-full"
               value="{{ old('density') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Price</span>
            <input
               type="number"
               name="price"
               inputmode="decimal"
               step="0.01"
               class="p-2 w-full"
               value="{{ old('price') }}"
            />
         </label>

         <label class="block">
            <span class="text-sm">Notes</span>
            <textarea name="notes" rows="4" class="p-2 w-full"> {{ old('notes') }}</textarea>
         </label>

         {{-- Alpine.js to enable multiple uploads --}}
         <div x-data="{ count: 1, firstSelected: false }" class="space-y-2 pb-4">
            <label class="block text-sm">Files</label>
            <template x-for="i in count" :key="i">
               <input
                  type="file"
                  name="files[]"
                  multiple
                  class="pb-2 w-full text-sm file:mr-4 file:rounded-md file:px-4 file:py-1 file:text-sm file:border-0 file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer"
                  @change="firstSelected = true"
               />
            </template>
            <button
               class="text-s underline text-indigo-900 hover:text-indigo-600 dark:text-indigo-300 dark:hover:text-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900"
               type="button"
               @click="count++"
               x-show="firstSelected"
            >
               Add another file
            </button>
         </div>

         <div class="flex gap-2">
            <x-primary-button type="submit" class="bg-green-600 hover:bg-green-700">
               SAVE
            </x-primary-button>
            <x-cancel-link href="{{ route('materials.show', $material) }}">CANCEL</x-cancel-link>
         </div>
      </form>
   </div>
</x-app-layout>
