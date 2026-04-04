<x-app-layout>
   @php
      $currentPyramid = collect(old("pyramid", $material->pyramid ?? []));
      $isMaterialInBlend = $material->blendIngredients()->exists();
   @endphp

   <x-slot name="header">
      <h2 class="font-semibold text-xl">Edit Material</h2>
   </x-slot>

   <div class="p-4">
      @if (session("error"))
         <x-flash type="error">{{ session("error") }}</x-flash>
      @endif

      <form
         id="material-update-{{ $material->id }}"
         method="POST"
         action="{{ route("materials.update", $material) }}"
         class="space-y-3"
      >
         @csrf
         @method("PATCH")

         <label class="block">
            <span class="text-sm">Name *</span>
            <input
               type="text"
               name="name"
               class="p-2 w-full"
               required
               value="{{ old("name", $material->name) }}"
            />
            @error("name")
               <div class="text-red-500 text-sm">{{ $message }}</div>
            @enderror
         </label>

         <label class="block">
            <span class="text-sm">Botanical</span>
            <input
               type="text"
               name="botanical"
               class="p-2 w-full"
               value="{{ old("botanical", $material->botanical) }}"
            />
         </label>

         <div class="space-y-6">
            {{-- Pyramid --}}
            <div class="space-y-2">
               <span class="text-sm font-medium">Pyramid</span>
               <div class="flex flex-wrap gap-3">
                  <label class="inline-flex items-center gap-2">
                     <input
                        class="rounded"
                        type="checkbox"
                        name="pyramid[]"
                        value="top"
                        @checked($currentPyramid->contains("top"))
                     />
                     <span class="text-sm">Top</span>
                  </label>
                  <label class="inline-flex items-center gap-2">
                     <input
                        class="rounded"
                        type="checkbox"
                        name="pyramid[]"
                        value="heart"
                        @checked($currentPyramid->contains("heart"))
                     />
                     <span class="text-sm">Heart</span>
                  </label>
                  <label class="inline-flex items-center gap-2">
                     <input
                        class="rounded"
                        type="checkbox"
                        name="pyramid[]"
                        value="base"
                        @checked($currentPyramid->contains("base"))
                     />
                     <span class="text-sm">Base</span>
                  </label>
               </div>
            </div>

            @error("pyramid")
               <div class="text-red-500 text-sm">{{ message }}</div>
            @enderror

            @error("pyramid.*")
               <div class="text-red-500 text-sm">{{ message }}</div>
            @enderror

            {{-- Families --}}
            <div class="space-y-2">
               <span class="text-sm">Families</span>
               <div class="flex flex-wrap gap-3">
                  @foreach (config("materials.families") as $v)
                     <label class="inline-flex items-center gap-2">
                        <input
                           class="rounded"
                           type="checkbox"
                           name="families[]"
                           value="{{ $v }}"
                           @checked(collect(old("families", $material->families ?? []))->contains($v))
                        />
                        <span class="text-sm">{{ ucfirst($v) }}</span>
                     </label>
                  @endforeach
               </div>
            </div>

            {{-- Functions --}}
            <div class="space-y-2">
               <span class="text-sm">Functions</span>
               <div class="flex flex-wrap gap-3">
                  @foreach (config("materials.functions") as $v)
                     <label class="inline-flex items-center gap-2">
                        <input
                           class="rounded"
                           type="checkbox"
                           name="functions[]"
                           value="{{ $v }}"
                           @checked(collect(old("functions", $material->functions ?? []))->contains($v))
                        />
                        <span class="text-sm">{{ ucfirst($v) }}</span>
                     </label>
                  @endforeach
               </div>
            </div>

            {{-- Effects --}}
            <div class="space-y-2">
               <span class="text-sm">Effects</span>
               <div class="flex flex-wrap gap-3">
                  @foreach (config("materials.effects") as $v)
                     <label class="inline-flex items-center gap-2">
                        <input
                           class="rounded"
                           type="checkbox"
                           name="effects[]"
                           value="{{ $v }}"
                           @checked(collect(old("effects", $material->effects ?? []))->contains($v))
                        />
                        <span class="text-sm">{{ ucfirst($v) }}</span>
                     </label>
                  @endforeach
               </div>
            </div>

            {{-- Safety --}}
            <div class="space-y-2">
               <span class="text-sm">Safety</span>
               <div class="flex flex-wrap gap-3">
                  @foreach (config("materials.safety") as $v)
                     <label class="inline-flex items-center gap-2">
                        <input
                           class="rounded"
                           type="checkbox"
                           name="safety[]"
                           value="{{ $v }}"
                           @checked(collect(old("safety", $material->safety ?? []))->contains($v))
                        />
                        <span class="text-sm">{{ ucfirst($v) }}</span>
                     </label>
                  @endforeach
               </div>
            </div>

            {{-- IFRA max % --}}
            <div class="space-y-2">
               <label class="block">
                  <span class="text-sm font-medium">IFRA max %</span>
                  <input
                     class="p-2 w-full"
                     type="number"
                     inputmode="decimal"
                     name="ifra_max_pct"
                     step="0.01"
                     min="0"
                     max="100"
                     value="{{ old("ifra_max_pct", $material->ifra_max_pct) }}"
                     placeholder="e.g. 1.0"
                  />
               </label>
            </div>
         </div>

         <label class="block">
            <span class="text-sm">Notes</span>
            <textarea name="notes" rows="4" class="p-2 w-full">
{{ old("notes", $material->notes) }}</textarea
            >
         </label>
      </form>

      <div class="mt-4 flex gap-2">
         <x-primary-button
            type="submit"
            form="material-update-{{ $material->id }}"
            class="bg-green-600 hover:bg-green-700"
         >
            SAVE
         </x-primary-button>

         <x-cancel-link href="{{ route('materials.index') }}#material-{{ $material->id }}">
            CANCEL
         </x-cancel-link>

         <form
            method="POST"
            action="{{ route("materials.destroy", $material) }}"
            @if (! $isMaterialInBlend)
                onsubmit="return confirm('Delete {{ $material->name }}?')"
            @endif
         >
            @csrf
            @method("DELETE")
            <x-danger-button>DELETE</x-danger-button>
         </form>
      </div>
   </div>
</x-app-layout>
