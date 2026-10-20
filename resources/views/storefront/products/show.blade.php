@extends('layouts.app')

@php
    $isSellerStore = isset($storeSeller) && $storeSeller;
    $storeName = $isSellerStore
        ? trim($storeSeller->first_name . ' ' . $storeSeller->last_name)
        : 'printore';
    $storeHomeUrl = $isSellerStore
        ? route('stores.show', $storeSeller)
        : route('home');
    $storeSearchUrl = $isSellerStore
        ? route('stores.search', $storeSeller)
        : route('products.search');
@endphp

@section('title', $product->title . ' | ' . ($isSellerStore ? 'متجر ' . $storeName : 'printore'))

@section('content')
    @php
        $availableVariants = $product->variants->where('stock', '>', 0)->values();
        $selectedVariant = $availableVariants->firstWhere('id', (int) old('product_variant_id'))
            ?? $availableVariants->first();
        $variantOptions = $availableVariants->map(fn($variant) => [
            'id' => $variant->id,
            'size' => $variant->size,
            'color' => $variant->color,
            'stock' => $variant->stock,
        ])->values();
    @endphp

    <div class="container py-5">
        <div class="mb-4">
            <a class="text-brand text-decoration-none fw-semibold" href="{{ $storeHomeUrl }}#products">
                {{ $isSellerStore ? 'العودة لمتجر ' . $storeName : 'العودة للمنتجات' }}
            </a>
            @if ($isSellerStore)
                <div
                    class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-2 rounded-4 bg-white border p-3 shadow-sm">
                    <div>
                        <small class="text-muted-custom d-block">أنت تتسوق الآن داخل</small>
                        <strong class="text-brand">متجر {{ $storeName }}</strong>
                    </div>
                    <a class="btn btn-outline-primary" href="{{ $storeSearchUrl }}">كل منتجات المتجر</a>
                </div>
            @endif
            <h1 class="fw-bold mt-2 mb-1">{{ $product->title }}</h1>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="product-main-image-frame dashboard-card card border-0 overflow-hidden">
                    @if ($product->cover_image)
                        <img class="product-detail-image" src="{{ $product->cover_image_url }}" alt="{{ $product->title }}">
                    @else
                        <div
                            class="product-detail-placeholder d-flex align-items-center justify-content-center bg-light text-muted">
                            لا توجد صورة رئيسية</div>
                    @endif
                </div>

                @if ($product->pictures->isNotEmpty())
                    <div class="product-gallery mt-3">
                        @foreach ($product->pictures as $picture)
                            <div class="product-gallery-item">
                                <a class="product-thumbnail-frame d-block dashboard-card card border-0 overflow-hidden"
                                    href="{{ $picture->picture_url }}" target="_blank" rel="noopener">
                                    <img class="product-detail-image" src="{{ $picture->picture_url }}"
                                        alt="صورة إضافية لـ {{ $product->title }}">
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="col-lg-5">
                <div class="dashboard-card card border-0 mb-4">
                    <div class="card-body p-4">
                        @if ($product->discount_amount > 0)
                            <span class="badge text-bg-danger mb-2">خصم {{ number_format($product->discount_amount, 2) }}
                                جنيه</span>
                            <span class="text-muted-custom text-decoration-line-through d-block">
                                {{ number_format($product->price, 2) }} جنيه
                            </span>
                        @endif
                        <strong class="text-brand fs-2 d-block mb-4">
                            {{ number_format($product->price_after_discount, 2) }} جنيه
                        </strong>

                        <div class="d-flex justify-content-between py-3 border-top">
                            <span class="text-muted-custom">المتوفر</span>
                            @if ($product->stock > 0)
                                <strong>{{ $product->stock }} قطعة</strong>
                            @else
                                <span class="badge text-bg-secondary">نفد المخزون</span>
                            @endif
                        </div>

                        @if ($availableVariants->isNotEmpty())
                            <div class="py-3 border-top">
                                <span class="text-muted-custom d-block mb-2">الاختيارات المتاحة</span>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($availableVariants as $variant)
                                        <span class="badge text-bg-light border text-dark">
                                            {{ $variant->size }} — {{ $variant->color }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @auth
                            @if (auth()->user()->hasRole(\App\Enums\RoleName::USER) && $availableVariants->isNotEmpty())
                                <form class="product-options d-grid gap-3 mt-3" id="product-cart-form" method="POST"
                                    action="{{ route('cart.items.store') }}">
                                    @csrf
                                    <div>
                                        <h2 class="h5 fw-bold mb-1">اختار المناسب ليك</h2>
                                        <p class="text-muted-custom small mb-0">المقاسات المعروضة بتتغير حسب اللون.</p>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <label class="form-label fw-semibold d-flex align-items-center gap-2"
                                                for="variant-color">
                                                <span class="option-step">1</span>
                                                اللون
                                            </label>
                                            <select class="form-select @error('product_variant_id') is-invalid @enderror"
                                                id="variant-color" required>
                                                @foreach ($availableVariants->pluck('color')->unique() as $color)
                                                    <option value="{{ $color }}" @selected($selectedVariant?->color === $color)>
                                                        {{ $color }}
                                                        ({{ $availableVariants->where('color', $color)->count() }} مقاس)
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fw-semibold d-flex align-items-center gap-2"
                                                for="variant-size">
                                                <span class="option-step">2</span>
                                                المقاس
                                            </label>
                                            <select class="form-select" id="variant-size" required></select>
                                        </div>
                                    </div>
                                    <input id="product_variant_id" name="product_variant_id" type="hidden"
                                        value="{{ $selectedVariant?->id }}">

                                    @error('product_variant_id')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror

                                    <div class="variant-summary" id="variant-summary" role="status" aria-live="polite"></div>

                                    <div>
                                        <label class="form-label fw-semibold d-flex align-items-center gap-2"
                                            for="variant-quantity">
                                            <span class="option-step">3</span>
                                            الكمية
                                        </label>
                                        <div class="variant-quantity-picker">
                                            <button class="btn btn-light quantity-action" type="button" data-action="decrease"
                                                aria-label="تقليل الكمية">−</button>
                                            <input class="form-control text-center @error('quantity') is-invalid @enderror"
                                                id="variant-quantity" name="quantity" type="number" min="1"
                                                max="{{ $selectedVariant?->stock }}" value="{{ old('quantity', 1) }}"
                                                inputmode="numeric" aria-label="الكمية" required>
                                            <button class="btn btn-light quantity-action" type="button" data-action="increase"
                                                aria-label="زيادة الكمية">+</button>
                                        </div>
                                        @error('quantity')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <button class="btn btn-brand w-100" id="add-to-cart-button" type="submit">
                                        أضف الاختيار للسلة
                                    </button>
                                </form>
                            @endif
                        @else
                            @if ($product->stock > 0)
                                <a class="btn btn-brand w-100 mt-3" href="{{ route('login') }}">سجل الدخول للإضافة للسلة</a>
                            @endif
                        @endauth
                    </div>
                </div>

                <div class="dashboard-card card border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">وصف المنتج</h2>
                        <p class="mb-0" style="white-space: pre-line;">{{ $product->description }}</p>
                    </div>
                </div>

                <div class="dashboard-card card border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">التصنيفات</h2>
                        @forelse ($product->sub_categories as $subCategory)
                            <span class="badge text-bg-light border text-dark me-1 mb-1">
                                {{ $subCategory->category->title }} / {{ $subCategory->title }}
                            </span>
                        @empty
                            <p class="text-muted-custom mb-0">لا توجد تصنيفات مرتبطة.</p>
                        @endforelse
                    </div>
                </div>

                <div class="dashboard-card card border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">خصائص المنتج</h2>
                        @if ($product->attributes->isEmpty())
                            <p class="text-muted-custom mb-0">لا توجد خصائص مسجلة.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <tbody>
                                        @foreach ($product->attributes as $attribute)
                                            <tr>
                                                <th class="ps-0">{{ $attribute->key }}</th>
                                                <td class="text-end pe-0">{{ $attribute->value }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .product-options {
            padding: 1rem;
            border: 1px solid rgba(91, 76, 240, .16);
            border-radius: 1rem;
            background: rgba(238, 236, 255, .42);
        }

        .option-step {
            display: inline-grid;
            width: 1.65rem;
            height: 1.65rem;
            color: #fff;
            font-size: .78rem;
            place-items: center;
            border-radius: 50%;
            background: var(--brand);
        }

        .variant-summary {
            padding: .8rem 1rem;
            color: var(--brand-dark);
            font-size: .9rem;
            font-weight: 600;
            border-radius: .8rem;
            background: #fff;
        }

        .variant-summary.low-stock {
            color: #b54708;
            background: #fffaeb;
        }

        .variant-quantity-picker {
            display: grid;
            grid-template-columns: 3.15rem minmax(4rem, 1fr) 3.15rem;
            gap: .5rem;
        }

        .variant-quantity-picker .quantity-action {
            padding-inline: 0;
            font-size: 1.35rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const variants = @json($variantOptions);
            const variantInput = document.getElementById('product_variant_id');
            const colorSelect = document.getElementById('variant-color');
            const sizeSelect = document.getElementById('variant-size');
            const quantityInput = document.getElementById('variant-quantity');
            const summary = document.getElementById('variant-summary');
            const form = document.getElementById('product-cart-form');
            const submitButton = document.getElementById('add-to-cart-button');

            if (!variantInput || !colorSelect || !sizeSelect || !quantityInput || !summary || !form || !submitButton) {
                return;
            }

            const clampQuantity = value => Math.min(
                Math.max(Number(value) || 1, 1),
                Number(quantityInput.max)
            );

            const syncVariant = () => {
                const variant = variants.find(item =>
                    item.color === colorSelect.value && item.size === sizeSelect.value
                );

                if (!variant) {
                    return;
                }

                variantInput.value = variant.id;
                quantityInput.max = variant.stock;
                quantityInput.value = clampQuantity(quantityInput.value);
                summary.textContent = `اختيارك: ${variant.color}، مقاس ${variant.size} — متاح ${variant.stock} قطعة`;
                summary.classList.toggle('low-stock', variant.stock <= 3);
            };

            const renderSizes = preferredSize => {
                const sizes = variants.filter(variant => variant.color === colorSelect.value);
                sizeSelect.replaceChildren(...sizes.map(variant => {
                    const option = document.createElement('option');
                    option.value = variant.size;
                    option.textContent = `${variant.size} (${variant.stock} متاح)`;
                    option.selected = variant.size === preferredSize;

                    return option;
                }));
                syncVariant();
            };

            colorSelect.addEventListener('change', () => renderSizes());
            sizeSelect.addEventListener('change', syncVariant);
            quantityInput.addEventListener('change', () => {
                quantityInput.value = clampQuantity(quantityInput.value);
            });
            form.querySelectorAll('.quantity-action').forEach(button => {
                button.addEventListener('click', () => {
                    const direction = button.dataset.action === 'increase' ? 1 : -1;
                    quantityInput.value = clampQuantity(Number(quantityInput.value) + direction);
                });
            });
            form.addEventListener('submit', () => {
                submitButton.disabled = true;
                submitButton.textContent = 'جارٍ الإضافة...';
            });
            renderSizes(@json($selectedVariant?->size));
        });
    </script>
@endpush