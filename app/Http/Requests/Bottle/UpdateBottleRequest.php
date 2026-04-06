<?php

namespace App\Http\Requests\Bottle;

use App\Enums\ExtractionMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum as EnumRule;

class UpdateBottleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_url' => ['nullable', 'url'],
            'batch_code' => ['nullable', 'string', 'max:255'],
            'method' => ['required', new EnumRule(ExtractionMethod::class)],
            'plant_part' => ['nullable', 'string', 'max:255'],
            'origin_country' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'density' => ['nullable', 'numeric', 'between:0,2'],
            'volume_ml' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'remove_files' => ['sometimes', 'array'],
            'remove_files.*' => ['integer'],
            'files' => ['sometimes', 'array'],
            'files.*' => ['file', 'max:5120'],
        ];
    }
}
