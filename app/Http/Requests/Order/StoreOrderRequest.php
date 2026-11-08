<?php

namespace App\Http\Requests\Order;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:15'],
            'address_id' => [
                'nullable',
                'integer',
                Rule::exists(Address::class, 'id')->where('user_id', $this->user()->id),
            ],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'wallet'])],
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.exists' => 'العنوان المختار لا يخص حسابك.',
            'payment_method.in' => 'طريقة الدفع المختارة غير صحيحة.',
        ];
    }
}
