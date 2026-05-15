<x-app-layout>
   <x-slot name="header">
      <h2 class="font-semibold text-xl text-slate-900 dark:text-gray-100 leading-tight">
         {{ __('Blends') }}
      </h2>
   </x-slot>

   <div class="p-6 space-y-6">
      <div>
         <x-link href="{{ route('blends.create') }}">Create Blend</x-link>
      </div>

      @if (session('success'))
         <x-flash type="success">{{ session('success') }}</x-flash>
      @endif

      @if ($blends->isNotEmpty())
         <div class="space-y-3">
            @foreach ($blends as $blend)
               <div
                  class="relative"
                  x-data="{ editing: {{ $errors->has('name') ? 'true' : 'false' }} }"
               >
                  <a
                     href="{{ route('blends.show', $blend) }}"
                     class="card card-hover card-focus block px-4 py-3 rounded-md text-sm font-semibold"
                  >
                     <x-editable-blend-name :blend="$blend" />
                  </a>
                  <div class="absolute top-2 right-2">
                     @include(
                        'blends.partials.actions-dropdown',
                        [
                           'blend' => $blend,
                        ]
                     )
                  </div>
               </div>
            @endforeach
         </div>
      @endif
   </div>
</x-app-layout>
