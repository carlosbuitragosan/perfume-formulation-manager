<x-dropdown>
   @php
      $isMaterialInBlend = $material->blendIngredients()->exists();
   @endphp

   <x-slot name="trigger">
      <button
         type="button"
         class="inline-flex items-center justify-center p-1 rounded-full text-slate-600 hover:text-slate-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900"
      >
         @include("icons.actions")
         <span class="sr-only">Bottle actions</span>
      </button>
   </x-slot>
   <x-slot name="content">
      <x-dropdown-link :href="route('materials.show', $material)">View Bottles</x-dropdown-link>

      <x-dropdown-link :href="route('materials.edit', $material)">Edit Material</x-dropdown-link>

      <form
         method="POST"
         action="{{ route("materials.destroy", $material) }}"
         class="bottle-delete-form"
         @if (! $isMaterialInBlend)
             onsubmit="return confirm('Delete {{ $material->name }}?')"
         @endif
      >
         @csrf
         @method("DELETE")
         <button
            type="submit"
            class="block w-full px-4 py-2 text-left text-sm leading-5 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-300 dark:hover:bg-red-900/40 dark:hover:text-red-200 focus:outline-none"
         >
            Delete Material
         </button>
      </form>
   </x-slot>
</x-dropdown>
