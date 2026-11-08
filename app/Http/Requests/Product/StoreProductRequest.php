<?php

namespace App\Http\Requests\Product;

use App\Support\ProductVariantOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
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
            'title' => 'required|string|max:50',
            'description' => 'required|string|max:1000',
            'cover_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5000',
            'price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0|lte:price',
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.options' => ['nullable', 'array'],
            'variants.*.options.*.key' => ['required', 'string', 'max:50'],
            'variants.*.options.*.value' => ['required', 'string', 'max:100'],
            'product_pictures' => 'nullable|array',
            'product_pictures.*' => 'image|mimes:jpg,jpeg,png,webp|max:5000',
            'sub_categories' => 'required|array|min:1',
            'sub_categories.*' => 'exists:sub_category,id',
            'attributes' => 'sometimes|array|nullable',
            'attributes.*.key' => 'required|string|max:50',
            'attributes.*.value' => 'required|string|max:255',
            'attributes.*.numeric_value' => 'nullable|numeric',
            'attributes.*.unit' => 'nullable|string|max:30',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $seen = [];
            $variants = $this->input('variants', []);

            if (! is_array($variants)) {
                return;
            }

            foreach ($variants as $index => $variant) {
                if (! is_array($variant)) {
                    continue;
                }

                $options = ProductVariantOptions::normalize($variant['options'] ?? []);
                $optionKeys = collect($variant['options'] ?? [])
                    ->map(fn ($option) => mb_strtolower(trim((string) ($option['key'] ?? ''))))
                    ->filter();

                if ($optionKeys->duplicates()->isNotEmpty()) {
                    $validator->errors()->add("variants.{$index}.options", 'لا يمكن تكرار اسم الاختيار داخل نفس النسخة.');
                }

                $key = ProductVariantOptions::signature($options);

                if (isset($seen[$key])) {
                    $validator->errors()->add("variants.{$index}.options", 'لا يمكن تكرار نفس تركيبة الاختيارات.');
                }

                $seen[$key] = true;
            }
        });
    }
}
