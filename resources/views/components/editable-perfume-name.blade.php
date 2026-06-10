@props([
   'perfume',
])
<div>
   <h2 x-show="!editing" class="mr-2 cursor-pointer">
      {{ $perfume->name }}
   </h2>
   <div x-show="editing">
      <form method="POST" action="{{ route('perfumes.update', $perfume) }}">
         @csrf
         @method('PUT')

         {{-- PERFUME NAME --}}
         <label class="border-b border-gray-400 inline-block">
            <input
               class="focus:ring-0 focus:ring-offset-0 border-none p-0"
               value="{{ old('name', $perfume->name) }}"
               type="text"
               name="name"
               x-ref="input"
               x-init="
                  if (editing) {
                     $refs.input.focus()
                  }
               "
               @blur="$el.form.submit()"
            />
         </label>

         {{-- Empty perfume name error for specific perfume --}}
         @if (session('perfume_id') === $perfume->id)
            @error('name')
               <div data-testid="error-name" class="mt-2">
                  <x-flash type="error" class="">{{ $message }}</x-flash>
               </div>
            @enderror
         @endif
      </form>
   </div>
</div>
