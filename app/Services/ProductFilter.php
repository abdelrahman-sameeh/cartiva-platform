<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class ProductFilter
{
    /** @param array<string, mixed> $filters */
    public function apply(Builder $query, array $filters): Builder
    {
        $this->applySearch($query, trim((string) ($filters['search'] ?? $filters['q'] ?? $filters['word'] ?? '')));
        $this->applyTaxonomyFilters($query, $filters);
        $this->applyAttributeFilters($query, $filters);
        $this->applyVariantFilters($query, $filters);

        if (! empty($filters['on_sale'])) {
            $query->where('products.discount_amount', '>', 0);
        }

        if (isset($filters['min_rating'])) {
            $query->where('products.rating_avg', '>=', $filters['min_rating']);
        }

        return $this->applySort($query, (string) ($filters['sort'] ?? 'latest'));
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = "%{$search}%";
        $query->where(function (Builder $query) use ($like) {
            $query->where('products.title', 'like', $like)
                ->orWhere('products.description', 'like', $like)
                ->orWhere('products.slug', 'like', $like)
                ->orWhereHas('attributes', function (Builder $query) use ($like) {
                    $query->where('key', 'like', $like)->orWhere('value', 'like', $like);
                })
                ->orWhereHas('sub_categories', function (Builder $query) use ($like) {
                    $query->where('title', 'like', $like)
                        ->orWhereHas('category', fn (Builder $query) => $query->where('title', 'like', $like));
                });
        });
    }

    /** @param array<string, mixed> $filters */
    private function applyTaxonomyFilters(Builder $query, array $filters): void
    {
        $categoryIds = $this->ids($filters['category_ids'] ?? [], $filters['category_id'] ?? null);
        $subCategoryIds = $this->ids($filters['sub_category_ids'] ?? [], $filters['sub_category_id'] ?? null);
        $sellerIds = $this->ids($filters['seller_ids'] ?? [], $filters['seller_id'] ?? null);

        if ($categoryIds !== []) {
            $query->whereHas('sub_categories', fn (Builder $query) => $query->whereIn('category_id', $categoryIds));
        }

        if ($subCategoryIds !== []) {
            $query->whereHas('sub_categories', fn (Builder $query) => $query->whereIn('sub_category.id', $subCategoryIds));
        }

        if ($sellerIds !== []) {
            $query->whereIn('products.seller_id', $sellerIds);
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyAttributeFilters(Builder $query, array $filters): void
    {
        foreach ($filters['attributes'] ?? [] as $key => $values) {
            $values = $this->values($values);

            if ($values !== []) {
                $query->whereHas('attributes', fn (Builder $query) => $query
                    ->where('key', mb_strtolower(trim((string) $key)))
                    ->whereIn('value', $values));
            }
        }

        foreach ($filters['attribute_ranges'] ?? [] as $key => $range) {
            if (! is_array($range) || ! isset($range['min']) && ! isset($range['max'])) {
                continue;
            }

            $query->whereHas('attributes', function (Builder $query) use ($key, $range) {
                $query->where('key', mb_strtolower(trim((string) $key)))
                    ->whereNotNull('numeric_value')
                    ->when(isset($range['min']), fn (Builder $query) => $query->where('numeric_value', '>=', $range['min']))
                    ->when(isset($range['max']), fn (Builder $query) => $query->where('numeric_value', '<=', $range['max']))
                    ->when(! empty($range['unit']), fn (Builder $query) => $query->where('unit', trim($range['unit'])));
            });
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyVariantFilters(Builder $query, array $filters): void
    {
        $options = collect($filters['options'] ?? [])
            ->mapWithKeys(fn ($values, $key) => [mb_strtolower(trim((string) $key)) => $this->values($values)])
            ->filter();
        $hasVariantFilters = $options->isNotEmpty()
            || isset($filters['min_price'])
            || isset($filters['max_price'])
            || ! empty($filters['in_stock']);

        if (! $hasVariantFilters) {
            return;
        }

        $query->whereHas('variants', function (Builder $query) use ($filters, $options) {
            if (! empty($filters['in_stock'])) {
                $query->where('stock', '>', 0);
            }

            if (isset($filters['min_price'])) {
                $query->whereRaw($this->effectivePriceSql().' >= CAST(? AS DECIMAL(15, 4))', [$filters['min_price']]);
            }

            if (isset($filters['max_price'])) {
                $query->whereRaw($this->effectivePriceSql().' <= CAST(? AS DECIMAL(15, 4))', [$filters['max_price']]);
            }

            foreach ($options as $key => $values) {
                $query->whereHas('options', fn (Builder $query) => $query
                    ->where('key', $key)
                    ->whereIn('value', $values));
            }
        });
    }

    private function applySort(Builder $query, string $sort): Builder
    {
        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            $query->select('products.*')->selectSub(function ($query) {
                $query->from('product_variants')
                    ->selectRaw('MIN('.$this->effectivePriceSql().')')
                    ->whereColumn('product_variants.product_id', 'products.id');
            }, 'effective_filter_price')
                ->orderBy('effective_filter_price', $sort === 'price_asc' ? 'asc' : 'desc');

            return $query->orderBy('products.id');
        }

        if ($sort === 'rating') {
            return $query->orderByDesc('products.rating_avg')->orderByDesc('products.rating_count');
        }

        if ($sort === 'popular') {
            return $query->orderByDesc('products.sold_count');
        }

        return $query->latest('products.created_at');
    }

    private function effectivePriceSql(): string
    {
        $price = 'COALESCE(product_variants.price, products.price) - products.discount_amount';

        return "CASE WHEN {$price} < 0 THEN 0 ELSE {$price} END";
    }

    /** @return array<int, int> */
    private function ids(mixed $values, mixed $single): array
    {
        return collect(is_array($values) ? $values : [])
            ->when($single !== null && $single !== '', fn ($ids) => $ids->push($single))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function values(mixed $values): array
    {
        return collect(is_array($values) ? $values : [$values])
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();
    }
}
