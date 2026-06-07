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
                  data-testid="blend-card"
                  data-blend-id="{{ $perfume->id }}"
                  class="card card-hover card-focus block px-4 py-3 rounded-md text-sm font-semibold"
               >
                  {{ $perfume->name }}
               </div>
            @endforeach
         </div>
      @endif
   </div>
</x-app-layout>
