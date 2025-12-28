<a
   {{
      $attributes->merge([
         'class' => 'inline-flex items-center px-4 py-2 rounded-md border border-transparent text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 transition ease-in-out duration-150',
      ])
   }}
>
   {{ $slot }}
</a>
