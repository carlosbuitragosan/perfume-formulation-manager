<x-dropdown>
   <x-slot name="trigger">
      <button
         type="button"
         class="inline-flex items-center justify-center p-1 rounded-full text-slate-600 hover:text-slate-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900"
      >
         @include('icons.actions')
         <span class="sr-only">Version actions</span>
      </button>
   </x-slot>
   <x-slot name="content">
      <x-dropdown-link :href="route('perfumes.create', $blendVersion)">
         Create Perfume
      </x-dropdown-link>
      <x-dropdown-link
         :href="route('blends.versions.create', [$blend, 'from' => $blendVersion->id])"
      >
         New Version
      </x-dropdown-link>

      <x-dropdown-link :href="route('blends.versions.edit', [$blend, $blendVersion])">
         Edit Version
      </x-dropdown-link>

      @if ($blend->versions->count() > 1)
         <form
            method="POST"
            action="{{ route('blends.versions.destroy', [$blend, $blendVersion]) }}"
            onsubmit="
               return confirm(
                  'Delete {{ $blend->name }}\'s Version {{ $blendVersion->version }}?',
               );
            "
         >
            @csrf
            @method('DELETE')
            <button
               type="submit"
               class="block w-full px-4 py-2 text-left text-sm leading-5 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-300 dark:hover:bg-red-900/40 dark:hover:text-red-200 focus:outline-none"
            >
               Delete Version
            </button>
         </form>
      @endif
   </x-slot>
</x-dropdown>
