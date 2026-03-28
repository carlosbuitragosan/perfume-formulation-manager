@php
   use Illuminate\Support\Carbon;
   use Illuminate\Support\Str;
   use App\Enums\ExtractionMethod;
@endphp

<x-app-layout>
   <x-slot name="header">
      <h2 class="font-semibold text-xl">View Bottles</h2>
      <span class="text-sm">{{ $material->name }}</span>
   </x-slot>

   <div class="p-4 space-y-4">
      <x-link
         href="{{ route('materials.bottles.create', $material) }}{{ request('ingredient') ?  '?ingredient='.request('ingredient') : '' }}"
      >
         Add Bottle
      </x-link>

      {{-- Succes delete bottle --}}

      @if (session('ok') && ! session('bottle_id'))
         <x-flash>{{ session('ok') }}</x-flash>
      @endif

      {{-- Stock --}}
      <div class="flex flex-col gap-2">
         @forelse ($material->bottles as $bottle)
            @php
               $enum = ExtractionMethod::tryFrom((string) $bottle->method);
               $isSelected = $bottle->id === $selectedBottleId;
            @endphp

            <div
               class="card relative border p-4 text-sm space-y-1 {{ $selectedBottleId && ! $isSelected ? 'opacity-30' : '' }}"
               id="bottle-{{ $bottle->id }}"
            >
               <div class="flex items-center gap-2 mb-1">
                  @if ($bottle->is_used)
                     <span
                        class="text-sm px-2 py-0.5 rounded font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-100"
                     >
                        In use
                     </span>
                  @endif

                  @if ($bottle->is_finished)
                     <span
                        class="text-sm px-2 py-0.5 rounded font-medium bg-yellow-100 text-yellow-900 dark:bg-yellow-900 dark:text-yellow-100"
                     >
                        Finished
                     </span>
                  @endif
               </div>

               @if (filled($bottle->supplier_name))
                  <div>
                     <span class="font-medium">Supplier:</span>
                     {{ $bottle->supplier_name }}
                  </div>
               @endif

               @if ($bottle->supplier_url)
                  <div class="flex gap-1 whitespace-nowrap">
                     <span class="font-medium">URL:</span>
                     <a
                        href="{{ $bottle->supplier_url }}"
                        title="{{ $bottle->supplier_url }}"
                        class="inline-block max-w-full truncate align-top underline text-indigo-600 hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 rounded-sm"
                        target="_blank"
                     >
                        {{ $bottle->supplier_url }}
                     </a>
                  </div>
               @endif

               @if (filled($bottle->batch_code))
                  <div>
                     <span class="font-medium">Batch:</span>
                     {{ $bottle->batch_code }}
                  </div>
               @endif

               @if (filled($bottle->method))
                  <div>
                     <span class="font-medium">Method:</span>
                     {{ $enum?->label() }}
                  </div>
               @endif

               @if (filled($bottle->plant_part))
                  <div>
                     <span class="font-medium">Plant part:</span>
                     {{ $bottle->plant_part }}
                  </div>
               @endif

               @if (filled($bottle->origin_country))
                  <div>
                     <span class="font-medium">Origin:</span>
                     {{ $bottle->origin_country }}
                  </div>
               @endif

               @if ($bottle->purchase_date)
                  <div>
                     <span class="font-medium">Purchase date:</span>
                     {{ Carbon::parse($bottle->purchase_date)->format('d/m/Y') }}
                  </div>
               @endif

               @if ($bottle->expiry_date)
                  <div>
                     <span class="font-medium">Expiry date:</span>
                     {{ Carbon::parse($bottle->expiry_date)->format('d/m/Y') }}
                  </div>
               @endif

               @if (filled($bottle->volume_ml))
                  <div>
                     <span class="font-medium">Volume:</span>
                     {{ rtrim(rtrim(number_format((float) $bottle->volume_ml, 2, '.', ''), '0'), '.') }}
                     ml
                  </div>
               @endif

               @if (filled($bottle->density))
                  <div>
                     <span class="font-medium">Density:</span>
                     {{ number_format((float) $bottle->density, 3) }} g/ml
                  </div>
               @endif

               @if (filled($bottle->price))
                  <div>
                     <span class="font-medium">Price:</span>
                     £{{ number_format((float) $bottle->price, 2) }}
                  </div>
               @endif

               @if (filled($bottle->notes))
                  <div>
                     <span class="font-medium">Notes:</span>
                     {{ $bottle->notes }}
                  </div>
               @endif

               @if ($bottle->files->isNotEmpty())
                  <div class="mt-2 flex flex-wrap gap-2 items-center">
                     <span>Files:</span>
                     @foreach ($bottle->files as $file)
                        <a
                           href="{{ Storage::disk('public')->url($file->path) }}"
                           class="bottle-file-link inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs border border-gray-300 text-slate-900 bg-white hover:border-indigo-500 hover:text-indigo-600 dark:border-gray-700 dark:text-gray-100 dark:bg-gray-900 dark:hover:border-indigo-500 dark:hover:text-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900"
                           target="_blank"
                        >
                           {{ $file->original_name }}
                        </a>
                     @endforeach
                  </div>
               @endif

               @if ($isSelected)
                  <x-flash type="ok">
                     This bottle is assigned to {{ $blendIngredient->material->name }} (Blend:
                     {{ $blendIngredient->blendVersion->blend->name }})
                  </x-flash>
               @endif

               {{-- Actions dropdown --}}
               <div class="absolute top-2 right-2">
                  @include(
                  'bottles.partials.actions-dropdown', ['bottle' => $bottle]                  )
               </div>

               {{-- Error message --}}
               @if (session('error') && session('bottle_id') == $bottle->id)
                  <x-flash type="error">{{ session('error') }}</x-flash>
               @endif

               {{-- Succes update message --}}
               @if (session('ok') && session('bottle_id') == $bottle->id)
                  <x-flash>{{ session('ok') }}</x-flash>
               @endif
            </div>
         @empty
            <div class="card p-4">
               <div class="text-sm text-gray-400">No bottles yet.</div>
            </div>
         @endforelse
      </div>
   </div>
</x-app-layout>
