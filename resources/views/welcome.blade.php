@extends('layouts.app')

@php
    $isSellerStore = isset($storeSeller) && $storeSeller;
    $storeName = $isSellerStore
        ? trim($storeSeller->first_name . ' ' . $storeSeller->last_name)
        : config('app.name');
    $storeSearchUrl = $isSellerStore
        ? route('stores.search', $storeSeller)
        : route('products.search');
    $productUrl = fn ($product) => $isSellerStore
        ? route('stores.products.show', [$storeSeller, $product])
        : route('stores.products.show', [$product->seller_id, $product]);
@endphp

@section('title', $isSellerStore ? 'متجر ' . $storeName . ' | ' . config('app.name') : config('app.name') . ' | كل احتياجاتك في مكان واحد')

@section('content')
    <div @class(['home-page', 'seller-store-page' => $isSellerStore])>
        <section class="container marketplace-hero-wrap">
            <div @class(['marketplace-hero', 'marketplace-hero-seller' => $isSellerStore])>
                <div class="marketplace-hero-content">
                    <span class="eyebrow">
                        <i class="bi bi-stars" aria-hidden="true"></i>
                        {{ $isSellerStore ? 'متجر موثوق على ' . config('app.name') : 'Marketplace لكل احتياجاتك' }}
                    </span>

                    @if ($isSellerStore)
                        <h1>أهلاً بيك في <span>متجر {{ $storeName }}</span></h1>
                        <p>اكتشف كل منتجات {{ $storeName }}، وقارن بين الاختيارات المتاحة بسهولة قبل ما تقرر.</p>
                    @else
                        <h1>كل اللي محتاجه، <span>من متاجر تثق فيها.</span></h1>
                        <p>دوّر، قارن، واختار المنتج المناسب من بائعين مختلفين في تجربة شراء واحدة بسيطة.</p>
                    @endif

                    <form class="hero-search" method="GET" action="{{ $storeSearchUrl }}" role="search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <label class="visually-hidden" for="hero-product-search">ابحث عن منتج</label>
                        <input id="hero-product-search" name="q" type="search"
                            placeholder="مثال: موبايل، خلاط، تيشيرت..." maxlength="100">
                        <button class="btn btn-brand" type="submit">ابحث دلوقتي</button>
                    </form>
                    @error('q')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror

                    <div class="hero-trust-list" aria-label="مميزات التسوق">
                        <span><i class="bi bi-shield-check" aria-hidden="true"></i> دفع آمن</span>
                        <span><i class="bi bi-box-seam" aria-hidden="true"></i> اختيارات متنوعة</span>
                        <span><i class="bi bi-headset" aria-hidden="true"></i> متابعة واضحة</span>
                    </div>

                    <div class="hero-actions">
                        <a class="hero-secondary-link" href="{{ route('stores.index') }}">
                            <i class="bi bi-shop" aria-hidden="true"></i>
                            {{ $isSellerStore ? 'الرجوع لكل المتاجر' : 'أو تصفح المتاجر' }}
                            <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        @unless ($isSellerStore)
            <section class="container marketplace-benefits" aria-label="ليه تختار Cativa؟">
                <article>
                    <span><i class="bi bi-search" aria-hidden="true"></i></span>
                    <div>
                        <strong>دوّر بسهولة</strong>
                        <small>بحث وفلاتر توصلك للمنتج المناسب</small>
                    </div>
                </article>
                <article>
                    <span><i class="bi bi-layers" aria-hidden="true"></i></span>
                    <div>
                        <strong>اختار النسخة الصح</strong>
                        <small>لون، مقاس أو مساحة حسب نوع المنتج</small>
                    </div>
                </article>
                <article>
                    <span><i class="bi bi-receipt" aria-hidden="true"></i></span>
                    <div>
                        <strong>طلب واحد واضح</strong>
                        <small>اشتري من أكتر من متجر وتابع طلبك</small>
                    </div>
                </article>
            </section>
        @endunless

        <section class="container products-section" id="products">
            <div class="section-heading">
                <div>
                    <span class="eyebrow eyebrow-compact">
                        {{ $isSellerStore ? 'اختيارات من البائع' : 'وصل حديثًا' }}
                    </span>
                    <h2>{{ $isSellerStore ? 'منتجات متجر ' . $storeName : 'منتجات تستاهل تكتشفها' }}</h2>
                    <p>{{ $isSellerStore ? 'كل المنتجات المعروضة هنا من المتجر ده بس.' : 'تصفح أحدث المنتجات المتاحة من متاجر مختلفة.' }}</p>
                </div>

                <a class="section-link" href="{{ $storeSearchUrl }}">
                    عرض كل المنتجات
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </a>
            </div>

            @if ($products->isEmpty())
                <div class="empty-state">
                    <span><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                    <h3>لسه مفيش منتجات متاحة</h3>
                    <p>المنتجات الجديدة هتظهر هنا أول ما البائعين يضيفوها.</p>
                    @unless ($isSellerStore)
                        <a class="btn btn-brand" href="{{ route('stores.index') }}">تصفح المتاجر</a>
                    @endunless
                </div>
            @else
                <div class="row g-3 g-lg-4">
                    @foreach ($products as $product)
                        <div class="col-6 col-lg-4 col-xl-3">
                            <x-storefront.product-card :product="$product" :url="$productUrl($product)" />
                        </div>
                    @endforeach
                </div>

                <div class="pagination-wrap">
                    {{ $products->fragment('products')->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
