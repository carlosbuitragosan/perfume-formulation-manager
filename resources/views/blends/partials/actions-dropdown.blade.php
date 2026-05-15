<x-dropdown>
   <x-slot name="trigger">
      <button
         type="button"
         class="inline-flex items-center justify-center p-1 rounded-full text-slate-600 hover:text-slate-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900"
      >
         @include('icons.actions')
         <span class="sr-only">Blend actions</span>
      </button>
   </x-slot>
   <x-slot name="content">
      <button
         type="button"
         @click="
         editing = true;
         $nextTick(function () {
          $refs.input.focus();
          $refs.input.setSelectionRange(
            $refs.input.value.length,
            $refs.input.value.length
            )
          })
          "
         class="block w-full px-4 py-2 text-left text-sm leading-5 text-slate-900 hover:bg-gray-100 hover:text-slate-900 dark:text-gray-100 dark:hover:bg-gray-700 dark:hover:text-white focus:outline-none"
      >
         Edit Blend Name
      </button>

      <form
         method="POST"
         action="{{ route('blends.destroy', $blend) }}"
         onsubmit="return confirm('Delete {{ $blend->name }}?');"
      >
         @csrf
         @method('DELETE')
         <button
            type="submit"
            class="block w-full px-4 py-2 text-left text-sm leading-5 text-slate-900 hover:bg-gray-100 hover:text-slate-900 dark:text-gray-100 dark:hover:bg-gray-700 dark:hover:text-white focus:outline-none"
         >
            Delete Blend
         </button>
      </form>
   </x-slot>
</x-dropdown>
