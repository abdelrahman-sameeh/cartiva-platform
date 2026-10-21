<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'word' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array', 'max:50'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'integer', 'exists:sub_category,id'],
            'sub_category_ids' => ['nullable', 'array', 'max:50'],
            'sub_category_ids.*' => ['integer', 'distinct', 'exists:sub_category,id'],
            'seller_id' => ['nullable', 'integer', 'exists:users,id'],
            'seller_ids' => ['nullable', 'array', 'max:50'],
            'seller_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'in_stock' => ['nullable', 'boolean'],
            'attributes' => ['nullable', 'array', 'max:20'],
            'attributes.*' => ['array', 'max:50'],
            'attributes.*.*' => ['string', 'max:255'],
            'attribute_ranges' => ['nullable', 'array', 'max:20'],
            'attribute_ranges.*.min' => ['nullable', 'numeric'],
            'attribute_ranges.*.max' => ['nullable', 'numeric'],
            'attribute_ranges.*.unit' => ['nullable', 'string', 'max:30'],
            'options' => ['nullable', 'array', 'max:20'],
            'options.*' => ['array', 'max:50'],
            'options.*.*' => ['string', 'max:100'],
            'sort' => ['nullable', Rule::in(['latest', 'price_asc', 'price_desc'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'limit' => ['nullable', 'integer', 'between:1,100'],
            'best_seller' => ['nullable', 'boolean'],
            'rating' => ['nullable', Rule::in(['high', 'low'])],
            'min_ratings' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
