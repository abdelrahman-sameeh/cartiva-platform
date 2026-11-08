<?php

namespace App\Http\Requests\Address;

use App\Helper\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country' => ['required', 'string', 'size:2', Rule::in(array_keys(Countries::LIST))],
            'city' => ['required', 'string', 'min:2', 'max:50'],
            'street' => ['required', 'string', 'min:5', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string', 'min:5', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ];
    }
}
