<x-app-layout>
   <x-slot name="header">
      <div id="header">
         <h2 class="font-semibold text-xl mr-2">Perfumes</h2>
      </div>
   </x-slot>

   <div class="p-4 space-y-4">
      @if ($perfumes->isNotEmpty())
         <div class="space-y-3">
            @foreach ($perfumes as $perfume)
               <div
                  data-testid="perfume-card"
                  data-perfume-id="{{ $perfume->id }}"
                  x-data="{
                     editing:
                        {{ $errors->has('name') && session('perfume_id') === $perfume->id ? 'true' : 'false' }},
                  }"
                  class="relative"
               >
                  <div
                     data-testid="perfume-link"
                     @click="if (!editing) window.location='{{ route('perfumes.show', $perfume) }}'"
                     class="card card-hover card-focus block px-4 py-3 rounded-md text-sm font-semibold"
                  >
                     <x-editable-perfume-name :perfume="$perfume" />
                  </div>
                  <div class="absolute top-2 right-2">
                     @include(
                        'perfumes.partials.actions-dropdown',
                        [
                           'perfume' => $perfume,
                        ]
                     )
                  </div>
               </div>
            @endforeach
         </div>
      @endif
   </div>
</x-app-layout>
