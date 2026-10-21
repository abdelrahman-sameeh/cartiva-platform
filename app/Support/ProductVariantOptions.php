<?php

namespace App\Support;

final class ProductVariantOptions
{
    /**
     * @param  array<int, array{key?: mixed, value?: mixed}>  $options
     * @return array<string, string>
     */
    public static function normalize(array $options): array
    {
        $normalized = [];

        foreach ($options as $option) {
            $key = mb_strtolower(trim((string) ($option['key'] ?? '')));
            $value = trim((string) ($option['value'] ?? ''));

            if ($key !== '' && $value !== '') {
                $normalized[$key] = $value;
            }
        }

        ksort($normalized, SORT_STRING);

        return $normalized;
    }

    /** @param array<string, string> $options */
    public static function signature(array $options): string
    {
        $normalized = [];

        foreach ($options as $key => $value) {
            $normalized[mb_strtolower(trim($key))] = mb_strtolower(trim($value));
        }

        ksort($normalized, SORT_STRING);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @param array<string, string> $options */
    public static function label(array $options): string
    {
        if ($options === []) {
            return 'المنتج الأساسي';
        }

        return collect($options)
            ->map(fn (string $value, string $key): string => "{$key}: {$value}")
            ->implode(' • ');
    }
}
