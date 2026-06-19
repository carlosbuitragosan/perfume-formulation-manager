@php
   use App\Enums\PyramidVariant;

   $base = 'block w-full md:inline-block md:w-auto md:min-w-[200px] text-left truncate px-2 py-1 rounded text-sm font-medium transition hover:opacity-80';

   $colorClass = PyramidVariant::tryFrom($variant)?->colorClass() ?? 'bg-slate-500 text-white';
@endphp

<span data-variant="{{ $variant }}" class="{{ $base }} {{ $colorClass }}">
   {{ $material }}
</span>
