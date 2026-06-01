@php
   use App\Enums\PyramidVariant;

   $base = 'inline-block min-w-[200px] px-2 py-1 rounded text-sm font-medium';

   $colorClass = PyramidVariant::tryFrom($variant)?->colorClass() ?? 'bg-gray-500 text-white';
@endphp

<span data-variant="{{ $variant }}" class="{{ $base }} {{ $colorClass }}">
   {{ $material }}
</span>
