<?php

namespace App\Http\Requests\Address;

use App\Helper\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country' => ['sometimes', 'required', 'string', 'size:2', Rule::in(array_keys(Countries::LIST))],
            'city' => ['sometimes', 'required', 'string', 'min:2', 'max:50'],
            'street' => ['sometimes', 'required', 'string', 'min:5', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'note' => ['sometimes', 'nullable', 'string', 'min:5', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric'],
            'longitude' => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
