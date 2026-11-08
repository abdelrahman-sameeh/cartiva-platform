<?php

namespace App\Http\Requests\Coupon;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => mb_strtoupper(trim((string) $this->code))]);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'alpha_dash',
                Rule::unique(Coupon::class, 'code')
                    ->where('seller_id', $this->user()->id),
            ],
            'percentage' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'max_usage' => ['required', 'integer', 'min:1'],
            'expire_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'كود الكوبون مستخدم بالفعل في حسابك.',
            'code.alpha_dash' => 'كود الكوبون يقبل الحروف والأرقام والشرطة والشرطة السفلية فقط.',
            'percentage.gt' => 'نسبة الخصم يجب أن تكون أكبر من صفر.',
            'percentage.lte' => 'نسبة الخصم لا يمكن أن تتجاوز 100%.',
            'expire_date.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون اليوم أو تاريخًا لاحقًا.',
        ];
    }
}
