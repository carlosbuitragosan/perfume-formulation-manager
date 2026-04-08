<?php

namespace App\Http\Requests\Blend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlendRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        // clean up incoming materials array
        // remove empty rows
        // reindex - values()
        // convert to plain PHP array - all()
        $materials = collect($this->input('materials', []))
            ->filter(function ($row) {
                $row = is_array($row) ? $row : [];
                $hasMaterial = ! empty($row['material_id']);
                $hasDrops = isset($row['drops']) && $row['drops'] !== '';

                return $hasMaterial || $hasDrops;
            })
            ->values()
            ->all();

        // Replace original request materials with cleaned version
        $this->merge(['materials' => $materials]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                Rule::unique('blends')->where(fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'materials' => ['required', 'array', 'min:2'],
            'materials.*.material_id' => [
                'required',
                'integer',
                Rule::exists('materials', 'id')
                    ->where(fn ($q) => $q->where('user_id', $this->user()->id)),
            ],
            'materials.*.drops' => ['required', 'integer', 'min:1', 'max:999'],
            'materials.*.dilution' => ['required', 'integer', 'in:25,10,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter a blend name',
            'name.unique' => 'You already have a blend with this name',
            'materials.required' => 'Add at least two ingredients',
            'materials.min' => 'Add at least two ingredients',
            'materials.*.material_id.required' => 'Select a material',
            'materials.*.drops.required' => 'Enter the number of drops',
            'materials.*.drops.integer' => 'Drops must be a whole number',
            'materials.*.drops.max' => 'Drops cannot exceed 999',
            'materials.*.drops.min' => 'Drops must be at least 1',
        ];
    }

    public function withValidator($validator)
    {
        // extra validation to prevent duplicate materials
        $validator->after(function ($validator) {
            $materials = $this->input('materials', []);

            $ids = collect($materials)
                ->pluck('material_id')
                ->filter(); // remove nulls

            if ($ids->count() !== $ids->unique()->count()) {
                $validator->errors()->add('materials', 'You can\'t use the same material twice.');
            }
        });
    }
}
