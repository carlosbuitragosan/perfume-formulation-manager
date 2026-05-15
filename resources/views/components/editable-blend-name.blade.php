@props([
   'blend',
])
<div>
   <h2 x-show="!editing" class="mr-2 cursor-pointer">
      {{ $blend->name }}
   </h2>
   <div x-show="editing">
      <form method="POST" action="{{ route('blends.update', $blend) }}">
         @csrf
         @method('PUT')

         {{-- BLEND NAME --}}
         <label class="border-b border-gray-400 inline-block">
            <input
               class="focus:ring-0 focus:ring-offset-0 border-none p-0"
               value="{{ old('name', $blend->name) }}"
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
         @error('name')
            <div data-testid="error-name">
               <x-flash type="error" class="">{{ $message }}</x-flash>
            </div>
         @enderror
      </form>
   </div>
</div>
