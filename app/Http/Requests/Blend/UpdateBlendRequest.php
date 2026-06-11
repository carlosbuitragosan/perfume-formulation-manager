<?php

namespace App\Http\Requests\Blend;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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

    public function failedValidation(Validator $validator)
    {
        $blend = $this->route('blend');

        // Override default redirect
        throw new HttpResponseException(
            redirect()
                ->back() // redirect to same page user was on
                ->withErrors($validator) // keep normal validation errors
                ->withInput() // keep old input
                ->with('blend_id', $blend->id) // pass on the ID
        );
    }
}
