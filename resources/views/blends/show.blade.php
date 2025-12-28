<x-app-layout>
   <x-slot name="header">
      <div class="flex items-center justify-between">
         <h2 class="font-semibold text-xl">{{ $blend->name }}</h2>
         <div class="flex gap-2">
            <x-link>EDIT</x-link>

            <form method="POST" action="" onsubmit="return confirm('Delete this material?')">
               @csrf
               @method('DELETE')
               <x-danger-button>DELETE</x-danger-button>
            </form>
         </div>
      </div>
   </x-slot>

   <div class="p-4 space-y-4">
      <div data-testid="blend-version" data-version="1.0" class="card p-4">
         <div class="font-semibold mb-3">Version 1.0</div>

         <div class="overflow-x-auto">
            <table class="w-full text-sm">
               <thead>
                  <tr class="text-left">
                     <th class="py-2">Material</th>
                     <th class="py-2"># Drops</th>
                     <th class="py-2">Dilution</th>
                     <th class="py-2">Pure %</th>
                  </tr>
               </thead>

               <tbody>
                  @foreach ($rows as $row)
                     <tr
                        data-testid="blend-ingredient-row"
                        data-material-id="{{ $row['material_id'] }}"
                     >
                        <td data-col="material" class="py-2">{{ $row['material_name'] }}</td>
                        <td data-col="drops" class="py-2">{{ $row['drops'] }}</td>
                        <td data-col="dilution" class="py-2">{{ $row['dilution'] }}</td>
                        <td data-col="pure_pct" class="py-2">{{ $row['pure_pct'] }}</td>
                     </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
      </div>
   </div>
</x-app-layout>
