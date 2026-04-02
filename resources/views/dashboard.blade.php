<x-app-layout>
   <x-slot name="header">
      <h2 class="font-semibold text-xl text-slate-900 dark:text-gray-100 leading-tight">
         {{ __('Dashboard') }}
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
               <a
                  href="{{ route('blends.show', $blend) }}"
                  class="card card-hover card-focus block px-4 py-3 rounded-md text-sm font-semibold"
               >
                  {{ $blend->name }}
               </a>
            @endforeach
         </div>
      @endif
   </div>
</x-app-layout>
