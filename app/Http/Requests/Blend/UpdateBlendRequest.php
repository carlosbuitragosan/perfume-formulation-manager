<?php

namespace App\Http\Requests\Blend;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBlendRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter a blend name',
        ];
    }
}
