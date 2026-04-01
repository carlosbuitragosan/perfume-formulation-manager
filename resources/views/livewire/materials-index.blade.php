<div class="space-y-3">
   <div class="relative" x-data="{ q: $wire.entangle('query') }">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
         @include('icons.search')
      </span>
      <input
         x-model="q"
         x-ref="search"
         class="w-full pl-10 pr-8 bg-red-600 dark:bg-red-700"
         type="search"
         name="query"
         inputmode="search"
         wire:model.live="query"
         autocomplete="off"
         spellcheck="false"
         enterkeyhint="search"
      />

      @if (filled($query))
         <button
            x-show="q !== ''"
            type="button"
            @click="q=''; $wire.resetPage()"
            aria-label="Clear search"
            class="absolute inset-y-0 right-0 pr-3 opacity-80 hover:opacity-100"
         >
            <span class="text-xl">&times;</span>
         </button>
      @endif
   </div>

   {{-- Button to create material --}}
   <x-link href="{{ route('materials.create') }}">Add Material</x-link>

   {{-- Succes delete material --}}
   @if (session('success') && ! session('material_id'))
      <x-flash type="success">{{ session('success') }}</x-flash>
   @endif

   {{-- Materials list --}}
   <div class="space-y-2">
      @forelse ($materials as $m)
         <div
            id="material-{{ $m->id }}"
            x-data
            @click="window.location='{{ route('materials.show', $m) }}'"
            @keydown.enter.prevent="window.location='{{ route('materials.show', $m) }}'"
            @keydown.space.prevent="window.location='{{ route('materials.show', $m) }}'"
            tabindex="0"
            class="card card-hover card-focus relative px-3 py-3 cursor-pointer"
         >
            {{-- view icon --}}
            <svg
               class="absolute top-1 right-1 w-5 h-5 text-green-500 dark:text-green-300 pointer-events-none opacity-80 group-hover:opacity-100 transition"
               fill="none"
               stroke="currentColor"
               stroke-width="2"
               viewBox="0 0 24 24"
            >
               <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7
           -1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
               />
               <circle cx="12" cy="12" r="3" />
            </svg>

            {{-- Name links to material edit page --}}
            <a
               href="{{ route('materials.edit', $m) }}"
               class="relative group card-focus inline-block mb-2 rounded px-2 py-1 pr-8 bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-900"
            >
               {{-- pencil icon --}}
               <svg
                  class="w-4 h-4 text-green-500 dark:text-green-300 absolute top-1 right-1"
                  fill="currentColor"
                  viewBox="0 0 24 24"
               >
                  <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" />
               </svg>

               <div class="font-medium text-slate-900 dark:text-gray-100">
                  {{ $m->name }}
               </div>
               <div class="text-xs mb-1 text-slate-600 dark:text-gray-400">
                  @if ($m->botanical)
                     {{ $m->botanical }}
                  @endif
               </div>
            </a>

            {{-- Tags --}}
            @php
               $colors = [
                  'pyramid' => 'bg-indigo-900 border-indigo-700 text-indigo-100',
                  'families' => 'bg-green-900 border-green-700 text-green-100',
                  'functions' => 'bg-yellow-900 border-yellow-700 text-yellow-100',
                  'safety' => 'bg-red-900 border-red-700 text-red-100',
                  'effects' => 'bg-purple-900 border-purple-700 text-purple-100',
                  'ifra' => 'bg-blue-900 border-blue-700 text-blue-100',
               ];

               $chips = [];

               // Pyramid
               foreach ((array) $m->pyramid ?? [] as $p) {
                  $chips[] = ['label' => Str::ucfirst($p), 'class' => $colors['pyramid']];
               }

               // Families
               foreach ((array) $m->families ?? [] as $p) {
                  $chips[] = ['label' => Str::ucfirst($p), 'class' => $colors['families']];
               }

               // Functions
               foreach ((array) $m->functions ?? [] as $p) {
                  $chips[] = ['label' => Str::ucfirst($p), 'class' => $colors['functions']];
               }

               // Effects
               foreach ((array) $m->effects ?? [] as $p) {
                  $chips[] = ['label' => Str::ucfirst($p), 'class' => $colors['effects']];
               }

               // Safety
               foreach ((array) $m->safety ?? [] as $p) {
                  $chips[] = ['label' => Str::ucfirst($p), 'class' => $colors['safety']];
               }

               // IFRA max %
               if (! is_null($m->ifra_max_pct)) {
                  $chips[] = [
                     'label' => 'IFRA4 ' . rtrim(rtrim(number_format((float) $m->ifra_max_pct, 2, '.', ''), '0'), '.') . '%',
                     'class' => $colors['ifra'],
                  ];
               }
            @endphp

            <div class="flex flex-wrap gap-2 justify-end">
               @foreach ($chips as $chip)
                  <span class="px-2 py-0.5 text-xs rounded border {{ $chip['class'] }}">
                     {{ $chip['label'] }}
                  </span>
               @endforeach
            </div>
            <div class="text-sm text-slate-600 dark:text-gray-400 mt-2">
               @if ($m->notes)
                  {{ $m->notes }}
               @endif
            </div>
            {{-- Show message when material has been added --}}
            @if (session('success') && session('material_id') == $m->id)
               <x-flash type="success">{{ session('success') }}</x-flash>
            @endif
         </div>
      @empty
         <div class="p-3 text-gray-400">No materials found.</div>
      @endforelse
   </div>
</div>
