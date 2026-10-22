@props(['product', 'url'])

@php
    $hasDiscount = (float) $product->discount_amount > 0;
    $discountPercentage = $hasDiscount && (float) $product->price > 0
        ? (int) round(((float) $product->discount_amount / (float) $product->price) * 100)
        : 0;
@endphp

<article class="product-card h-100">
    <a class="product-card-media" href="{{ $url }}" aria-label="عرض {{ $product->title }}">
        @if ($product->cover_image)
            <img src="{{ $product->cover_image_url }}" alt="{{ $product->title }}" loading="lazy">
        @else
            <span class="product-card-placeholder">
                <i class="bi bi-image" aria-hidden="true"></i>
                <small>لا توجد صورة</small>
            </span>
        @endif

        <span class="product-card-badges">
            @if ($hasDiscount)
                <span class="product-badge product-badge-sale">خصم {{ $discountPercentage }}%</span>
            @endif
            @if ($product->stock <= 0)
                <span class="product-badge product-badge-muted">غير متوفر</span>
            @elseif ($product->stock <= 5)
                <span class="product-badge product-badge-warning">آخر {{ $product->stock }} قطع</span>
            @endif
        </span>
    </a>

    <div class="product-card-body">
        <div class="product-card-categories">
            @foreach ($product->sub_categories->take(2) as $subCategory)
                <span>{{ $subCategory->title }}</span>
            @endforeach
        </div>

        <h3 class="product-card-title">
            <a href="{{ $url }}">{{ $product->title }}</a>
        </h3>

        <p class="product-card-description">{{ Str::limit($product->description, 72) }}</p>

        <div class="product-card-footer">
            <div class="product-card-price">
                @if ($hasDiscount)
                    <del>{{ number_format($product->price, 2) }} جنيه</del>
                @endif
                <strong>{{ number_format($product->price_after_discount, 2) }} جنيه</strong>
            </div>

            <a class="product-card-action" href="{{ $url }}" aria-label="عرض تفاصيل {{ $product->title }}">
                <i class="bi bi-arrow-up-left" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</article>
