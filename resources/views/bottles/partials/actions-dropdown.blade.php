<x-dropdown>
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
      <x-dropdown-link :href="route('bottles.edit', $bottle)">Edit</x-dropdown-link>

      @if ($bottle->is_active)
         <form method="POST" action="{{ route("bottles.finish", $bottle) }}">
            @csrf
            <button
               type="submit"
               class="block w-full px-4 py-2 text-left text-sm leading-5 text-slate-900 hover:bg-gray-100 hover:text-slate-900 dark:text-gray-100 dark:hover:bg-gray-700 dark:hover:text-white focus:outline-none"
            >
               Mark as finished
            </button>
         </form>
      @else
         <form method="POST" action="{{ route("bottles.reactivate", $bottle) }}">
            @csrf
            <button
               type="submit"
               class="block w-full px-4 py-2 text-left text-sm leading-5 text-slate-900 hover:bg-gray-100 hover:text-slate-900 dark:text-gray-100 dark:hover:bg-gray-700 dark:hover:text-white focus:outline-none"
            >
               Reactivate
            </button>
         </form>
      @endif

      <form
         method="POST"
         action="{{ route("bottles.destroy", $bottle) }}"
         class="bottle-delete-form"
         {{-- show confirmation only if bottle is not in use --}}
         @unless ($bottleInUse)
             onsubmit="return confirm('Are you sure you want to delete this bottle?')"
         @endunless
      >
         @csrf
         @method("DELETE")
         <button
            type="submit"
            class="block w-full px-4 py-2 text-left text-sm leading-5 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-300 dark:hover:bg-red-900/40 dark:hover:text-red-200 focus:outline-none"
         >
            Delete
         </button>
      </form>
   </x-slot>
</x-dropdown>
