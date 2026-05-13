<?php

namespace App\Http\Requests\Blend;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlendRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $materials = collect($this->input('materials', []))
            ->filter(function ($row) {
                $row = is_array($row) ? $row : [];
                $hasMaterial = ! empty($row['material_id']);
                $hasDrops = isset($row['drops']) && $row['drops'] !== '';

                return $hasMaterial || $hasDrops;
            })
            ->values()
            ->all();

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'materials' => $materials,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'materials' => ['required', 'array', 'min:2'],
            'materials.*.material_id' => [
                'required',
                'integer',
                Rule::exists('materials', 'id')
                    ->where(fn ($q) => $q->where('user_id', $this->user()->id)),
            ],
            'materials.*.drops' => ['required', 'integer', 'min:1', 'max:999'],
            'materials.*.dilution' => ['required', 'integer', Rule::in([25, 10, 1])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter a blend name',
            'materials.required' => 'Add at least two ingredients',
            'materials.min' => 'Add at least two ingredients',
            'materials.*.material_id.required' => 'Select a material',
            'materials.*.drops.required' => 'Enter the number of drops',
            'materials.*.drops.integer' => 'Drops must be a whole number',
            'materials.*.drops.max' => 'Drops cannot exceed 999',
        ];
    }

    public function after(): array
    {
        return [

            function ($validator) {
                $materials = $this->input('materials', []);

                $ids = collect($materials)
                    ->pluck('material_id')
                    ->filter();

                if ($ids->count() !== $ids->unique()->count()) {
                    $validator->errors()->add('materials', 'You can\'t use the same material twice.');
                }
            },
        ];
    }
}
