<x-app-layout>
   <x-slot name="header">
      <h2 class="font-semibold text-xl">Create Perfume</h2>
   </x-slot>

   <div class="p-4">
      <form
         id="create-perfume-form"
         method="POST"
         action="{{ route('versions.perfumes.store', $version) }}"
         class="space-y-3"
      >
         @csrf

         {{-- Perfume name --}}
         <label class="block">
            <span class="text-sm">Perfume Name</span>
            <input type="text" name="name" value="{{ old('name') }}" class="p-2 w-full" />
         </label>
         @error('name')
            <div data-testid="error-name">
               <x-flash type="error" class="">{{ $message }}</x-flash>
            </div>
         @enderror

         {{-- Bottle Size --}}
         <label class="block">
            <span class="text-sm">Bottle Size</span>
            <input type="text" name="size" value="{{ old('size') }}" class="p-2 w-full" />
         </label>
         @error('size')
            <div data-testid="error-size">
               <x-flash type="error" class="">{{ $message }}</x-flash>
            </div>
         @enderror

         {{-- Perfume Contentration --}}
         <label class="block">
            <span class="text-sm">Concentration</span>
            <input
               type="text"
               name="concentration"
               value="{{ old('concentration') }}"
               class="p-2 w-full"
            />
         </label>
         @error('concentration')
            <div data-testid="error-concentration">
               <x-flash type="error" class="">{{ $message }}</x-flash>
            </div>
         @enderror

         {{-- Carrier Type --}}
         <select name="carrier_type" class="w-full p-2">
            <option value="">Select Carrier Type</option>
            <option value="alcohol" @selected(old('carrier_type') === 'alcohol')>Alcohol</option>
            <option value="oil" @selected(old('carrier_type') === 'oil')>Oil</option>
            <option value="solid" @selected(old('carrier_type') === 'solid')>Solid</option>
         </select>

         {{-- Error message for carrier type --}}
         @error('carrier_type')
            <div data-testid="error-carrier_type">
               <x-flash type="error" class="">{{ $message }}</x-flash>
            </div>
         @enderror

         <div class="flex gap-2">
            <x-primary-button type="submit" class="bg-green-600 hover:bg-green-700">
               SAVE
            </x-primary-button>
            <x-cancel-link
               href="{{ route('blends.show', $version->blend).'#version-'.$version->id  }}"
            >
               CANCEL
            </x-cancel-link>
         </div>
      </form>
   </div>
</x-app-layout>
