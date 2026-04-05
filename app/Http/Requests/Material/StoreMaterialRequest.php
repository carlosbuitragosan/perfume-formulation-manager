<?php

namespace App\Http\Requests\Material;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialRequest extends FormRequest
{
    // this runs first
    protected function prepareForValidation()
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'botanical' => $this->filled('botanical')
            ? trim($this->input('botanical'))
            : null,
        ]);
    }

    // This runs second
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    // This runs third
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'botanical' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            'pyramid' => ['nullable', 'array'],
            'pyramid.*' => ['in:top,heart,base'],

            'families' => ['nullable', 'array'],
            'families.*' => [Rule::in(config('materials.families'))],

            'functions' => ['nullable', 'array'],
            'functions.*' => [Rule::in(config('materials.functions'))],

            'safety' => ['nullable', 'array'],
            'safety.*' => [Rule::in(config('materials.safety'))],

            'effects' => ['nullable', 'array'],
            'effects.*' => [Rule::in(config('materials.effects'))],

            'ifra_max_pct' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    // This runs after rules()
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $needle = mb_strtolower($this->input('name'));

            $exists = Material::where('user_id', auth()->id())
                ->whereRaw('LOWER(name) = ?', [$needle])
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'name',
                    'You already have a material with that name'
                );
            }
        });
    }
}
