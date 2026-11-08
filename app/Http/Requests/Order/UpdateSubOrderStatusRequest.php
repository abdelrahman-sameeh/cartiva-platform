<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['processing', 'shipped', 'completed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return ['status.in' => 'حالة الطلب المختارة غير صحيحة.'];
    }
}
