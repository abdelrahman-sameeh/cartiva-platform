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
    <div @class(['container pt-2 pb-5', 'seller-store-page' => $isSellerStore])>
        <div @class([
            'row align-items-center py-3 py-lg-4',
            'seller-store-hero p-4 p-lg-5 rounded-5 mb-4' => $isSellerStore,
        ])>
            <div class="col-12">
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3 py-2 mb-4">
                    {{ $isSellerStore ? 'متجر ' . $storeName : 'تسوّق من متاجر مميزة' }}
                </span>
                @if ($isSellerStore)
                    <h1 class="display-3 fw-bold lh-sm mb-4">أهلاً بك في <span class="text-brand">متجر {{ $storeName }}</span></h1>
                    <p class="lead text-muted-custom mb-4">كل المنتجات والنتائج هنا مقدمة من {{ $storeName }} فقط، لتجربة شراء واضحة داخل متجر واحد.</p>
                @else
                    <h1 class="display-3 fw-bold lh-sm mb-4">كل اللي بتدور عليه، <span class="text-brand">في مكان واحد.</span>
                    </h1>
                    <p class="lead text-muted-custom mb-4">اكتشف منتجات متنوعة من متاجر موثوقة على
                        {{ config('app.name') }}، واستمتع بتجربة شراء سهلة من البحث لحد استلام طلبك.</p>
                @endif

                <form class="mb-4" method="GET" action="{{ $storeSearchUrl }}" role="search">
                    <label class="visually-hidden" for="product-search">ابحث عن منتج</label>
                    <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden">
                        <input class="form-control border-0" id="product-search" name="q" type="search"
                            placeholder="ابحث عن أي منتج..." maxlength="100">
                        <button class="btn btn-brand px-4" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span>بحث</span></button>
                    </div>
                    @error('q')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </form>

                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-brand px-4" href="{{ $storeSearchUrl }}"><i class="bi bi-bag" aria-hidden="true"></i><span>تسوق الآن</span></a>
                    <a class="btn btn-outline-primary px-4" href="{{ route('stores.index') }}"><i class="bi bi-shop" aria-hidden="true"></i><span>تصفح المتاجر</span></a>
                    @auth
                        <a class="btn btn-outline-dark px-4" href="{{ route('dashboard') }}">الذهاب إلى حسابي</a>
                    @else
                        <a class="btn btn-outline-dark px-4" href="{{ route('register') }}">إنشاء حساب</a>
                    @endauth
                </div>
            </div>
        </div>

        <section class="pt-4 pb-5" id="products">
            <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-2 mb-4">
                <div>
                    <span class="text-brand fw-semibold">{{ $isSellerStore ? 'أحدث منتجات البائع' : 'أحدث المنتجات' }}</span>
                    <h2 class="fw-bold mb-1">{{ $isSellerStore ? 'منتجات متجر ' . $storeName : 'اكتشف منتجات المتجر' }}</h2>
                    <p class="text-muted-custom mb-0">{{ $isSellerStore ? 'كل المعروض هنا تابع لهذا المتجر فقط.' : 'تصفّح أحدث المنتجات المتاحة على ' . config('app.name') . '.' }}</p>
                </div>

                <a class="btn btn-outline-primary" href="{{ $storeSearchUrl }}">كل المنتجات والفلاتر</a>
            </div>

            @if ($products->isEmpty())
                <div class="dashboard-card card border-0">
                    <div class="card-body text-center p-5">
                        <div class="fs-1 mb-3">🔍</div>
                        <h3 class="h4 fw-bold">لا توجد منتجات متاحة بعد</h3>
                        <p class="text-muted-custom mb-0">ستظهر أحدث المنتجات هنا بمجرد إضافتها.</p>
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($products as $product)
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <article class="dashboard-card card border-0 h-100 overflow-hidden position-relative">
                                <a href="{{ $productUrl($product) }}">
                                    @if ($product->cover_image)
                                        <img class="w-100" style="height: 210px; object-fit: cover;"
                                            src="{{ $product->cover_image_url }}" alt="{{ $product->title }}">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center bg-light text-muted"
                                            style="height: 210px;">لا توجد صورة</div>
                                    @endif
                                </a>

                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="mb-2">
                                        @foreach ($product->sub_categories->take(2) as $subCategory)
                                            <span class="badge text-bg-light border text-dark mb-1">{{ $subCategory->title }}</span>
                                        @endforeach
                                    </div>
                                    <h3 class="h5 fw-bold mb-2">
                                        <a class="text-dark text-decoration-none stretched-link"
                                            href="{{ $productUrl($product) }}">
                                            {{ $product->title }}
                                        </a>
                                    </h3>
                                    <p class="text-muted-custom small mb-3">{{ Str::limit($product->description, 80) }}</p>

                                    <div class="d-flex align-items-end justify-content-between gap-2 mt-auto position-relative z-2">
                                        <div>
                                            @if ($product->discount_amount > 0)
                                                <small class="text-muted-custom text-decoration-line-through d-block">
                                                    {{ number_format($product->price, 2) }} جنيه
                                                </small>
                                            @endif
                                            <strong class="text-brand">{{ number_format($product->price_after_discount, 2) }}
                                                جنيه</strong>
                                        </div>
                                        <a class="btn btn-brand" href="{{ $productUrl($product) }}">عرض</a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $products->fragment('products')->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
