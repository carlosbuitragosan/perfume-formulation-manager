<x-app-layout>
   <x-slot name="header">
      <h2 class="font-semibold text-xl">Create Material</h2>
   </x-slot>
   <div class="p-4">
      <form method="post" action="{{ route('materials.store') }}" class="space-y-3">
         @csrf
         <label class="block">
            <span class="text-sm">Name *</span>
            <input type="text" name="name" required class="p-2 w-full" value="{{ old('name') }}" />
            @error('name')
               <div class="text-red-600 text-small">{{ $message }}</div>
            @enderror
         </label>

         <label class="block">
            <span class="text-sm">Botanical (Latin name)</span>
            <input
               type="text"
               name="botanical"
               class="p-2 w-full"
               value="{{ old('botanical') }}"
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
                        @checked(collect(old('pyramid', []))->contains('top'))
                     />
                     <span class="text-sm">Top</span>
                  </label>
                  <label class="inline-flex items-center gap-2">
                     <input
                        class="rounded"
                        type="checkbox"
                        name="pyramid[]"
                        value="heart"
                        @checked(collect(old('pyramid', []))->contains('heart'))
                     />
                     <span class="text-sm">Heart</span>
                  </label>
                  <label class="inline-flex items-center gap-2">
                     <input
                        class="rounded"
                        type="checkbox"
                        name="pyramid[]"
                        value="base"
                        @checked(collect(old('pyramid', []))->contains('base'))
                     />
                     <span class="text-sm">Base</span>
                  </label>
               </div>
            </div>

            @error('pyramid')
               <div class="text-red-500 text-sm">{{ message }}</div>
            @enderror

            @error('pyramid.*')
               <div class="text-red-500 text-sm">{{ message }}</div>
            @enderror

            {{-- Families --}}
            <div class="space-y-2">
               <span class="text-sm">Families</span>
               <div class="flex flex-wrap gap-3">
                  @foreach (config('materials.families') as $v)
                     <label class="inline-flex items-center gap-2">
                        <input
                           class="rounded"
                           type="checkbox"
                           name="families[]"
                           value="{{ $v }}"
                           @checked(collect(old('families', []))->contains($v))
                        />
                        <span>{{ ucfirst($v) }}</span>
                     </label>
                  @endforeach
               </div>
            </div>

            {{-- Functions --}}
            <div class="space-y-2">
               <span class="text-sm">Functions</span>
               <div class="flex flex-wrap gap-3">
                  @foreach (config('materials.functions') as $v)
                     <label class="inline-flex items-center gap-2">
                        <input
                           class="rounded"
                           type="checkbox"
                           name="functions[]"
                           value="{{ $v }}"
                           @checked(collect(old('functions', []))->contains($v))
                        />
                        <span>{{ ucfirst($v) }}</span>
                     </label>
                  @endforeach
               </div>
            </div>

            {{-- Effects --}}
            <div class="space-y-2">
               <span class="text-sm">Effects</span>
               <div class="flex flex-wrap gap-3">
                  @foreach (config('materials.effects') as $v)
                     <label class="inline-flex items-center gap-2">
                        <input
                           class="rounded"
                           type="checkbox"
                           name="effects[]"
                           value="{{ $v }}"
                           @checked(collect(old('Effects', []))->contains($v))
                        />
                        <span>{{ ucfirst($v) }}</span>
                     </label>
                  @endforeach
               </div>
            </div>

            {{-- Safety --}}
            <div class="space-y-2">
               <span class="text-sm">Safety</span>
               <div class="flex flex-wrap gap-3">
                  @foreach (config('materials.safety') as $v)
                     <label class="inline-flex items-center gap-2">
                        <input
                           class="rounded"
                           type="checkbox"
                           name="safety[]"
                           value="{{ $v }}"
                           @checked(collect(old('Safety', []))->contains($v))
                        />
                        <span>{{ ucfirst($v) }}</span>
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
                     value="{{ old('ifra_max_pct') }}"
                     placeholder="e.g. 1.0"
                  />
               </label>
            </div>
         </div>

         <label class="block">
            <span class="text-sm">Notes</span>
            <textarea name="notes" rows="4" class="p-2 w-full">{{ old('notes') }}</textarea>
         </label>

         <div class="flex gap-2">
            <x-primary-button type="submit" class="bg-green-600 hover:bg-green-700">
               SAVE
            </x-primary-button>
            <x-cancel-link href="{{ route('materials.index') }}">CANCEL</x-cancel-link>
         </div>
      </form>
   </div>
</x-app-layout>
