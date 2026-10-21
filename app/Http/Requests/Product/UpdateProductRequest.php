<?php

namespace App\Http\Requests\Product;

use App\Support\ProductVariantOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|string|min:3|max:50',
            'description' => 'sometimes|string|min:10|max:1000',
            'cover_image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price' => 'sometimes|numeric|min:0',
            'discount_amount' => 'sometimes|nullable|numeric|min:0|lte:price',
            'variants' => ['sometimes', 'required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer', 'distinct', 'exists:product_variants,id'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.options' => ['nullable', 'array'],
            'variants.*.options.*.key' => ['required', 'string', 'max:50'],
            'variants.*.options.*.value' => ['required', 'string', 'max:100'],
            'product_pictures' => 'sometimes|nullable|array',
            'product_pictures.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'sub_categories' => 'sometimes|array|min:1',
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
