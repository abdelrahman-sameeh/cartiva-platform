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
    $productUrl = fn ($product) => $isSellerStore
        ? route('stores.products.show', [$storeSeller, $product])
        : route('stores.products.show', [$product->seller_id, $product]);
@endphp

@section('title', $isSellerStore ? 'البحث في متجر ' . $storeName . ' | printore' : 'البحث عن المنتجات | printore')

@section('content')
    @php
        $activeFilters = collect([
            $filters['q'] !== '',
            !empty($filters['category_id']),
            !empty($filters['sub_category_id']),
            isset($filters['min_price']),
            isset($filters['max_price']),
            isset($filters['min_rating']),
            !empty($filters['in_stock']),
            !empty($filters['on_sale']),
        ])->filter()->count();
    @endphp

    <div class="container py-5">
        <div class="mb-4">
            <a class="text-brand text-decoration-none fw-semibold" href="{{ $storeHomeUrl }}">العودة للرئيسية</a>
            <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mt-2">
                <div>
                    <h1 class="fw-bold mb-1">{{ $isSellerStore ? 'ابحث في متجر ' . $storeName : 'ابحث في المنتجات' }}</h1>
                    <p class="text-muted-custom mb-0">{{ $isSellerStore ? 'كل النتائج والفلاتر محصورة في منتجات هذا البائع.' : 'استخدم البحث والفلاتر للوصول للمنتج المناسب.' }}</p>
                </div>
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3 py-2">
                    {{ $products->total() }} نتيجة
                </span>
            </div>
        </div>

        <form class="dashboard-card card border-0 mb-4" method="GET" action="{{ $storeSearchUrl }}"
            role="search">
            <div class="card-body p-3 p-md-4">
                <label class="visually-hidden" for="search-query">ابحث عن منتج</label>
                <div class="input-group input-group-lg">
                    <input class="form-control" id="search-query" name="q" type="search"
                        value="{{ $filters['q'] }}" placeholder="اسم المنتج أو الوصف أو الخاصية..." maxlength="100">
                    <button class="btn btn-brand px-4" type="submit">بحث</button>
                </div>
            </div>
        </form>

        <div class="row g-4 align-items-start">
            <aside class="col-lg-3">
                <form class="dashboard-card card border-0" method="GET" action="{{ $storeSearchUrl }}">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h2 class="h5 fw-bold mb-0">تصفية النتائج</h2>
                            @if ($activeFilters > 0)
                                <span class="badge text-bg-primary">{{ $activeFilters }}</span>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="filter-query">كلمة البحث</label>
                            <input class="form-control" id="filter-query" name="q" type="search"
                                value="{{ $filters['q'] }}" placeholder="مثال: هاتف">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="category_id">التصنيف الرئيسي</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">كل التصنيفات</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>
                                        {{ $category->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="sub_category_id">التصنيف الفرعي</label>
                            <select class="form-select" id="sub_category_id" name="sub_category_id">
                                <option value="">كل التصنيفات الفرعية</option>
                                @foreach ($categories as $category)
                                    @if ($category->sub_categories->isNotEmpty())
                                        <optgroup label="{{ $category->title }}">
                                            @foreach ($category->sub_categories as $subCategory)
                                                <option value="{{ $subCategory->id }}" @selected(($filters['sub_category_id'] ?? null) == $subCategory->id)>
                                                    {{ $subCategory->title }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">السعر بعد الخصم</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input class="form-control @error('min_price') is-invalid @enderror" name="min_price"
                                        type="number" min="0" step="0.01" value="{{ $filters['min_price'] ?? '' }}"
                                        placeholder="من">
                                </div>
                                <div class="col-6">
                                    <input class="form-control @error('max_price') is-invalid @enderror" name="max_price"
                                        type="number" min="0" step="0.01" value="{{ $filters['max_price'] ?? '' }}"
                                        placeholder="إلى">
                                </div>
                            </div>
                            @error('max_price')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="min_rating">أقل تقييم</label>
                            <select class="form-select" id="min_rating" name="min_rating">
                                <option value="">كل التقييمات</option>
                                @foreach ([4, 3, 2, 1] as $rating)
                                    <option value="{{ $rating }}" @selected(($filters['min_rating'] ?? null) == $rating)>
                                        {{ $rating }} نجوم فأكثر
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" id="in_stock" name="in_stock" type="checkbox" value="1"
                                @checked(!empty($filters['in_stock']))>
                            <label class="form-check-label" for="in_stock">المتوفر في المخزون فقط</label>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" id="on_sale" name="on_sale" type="checkbox" value="1"
                                @checked(!empty($filters['on_sale']))>
                            <label class="form-check-label" for="on_sale">المنتجات المخفّضة فقط</label>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="sort">الترتيب</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="latest" @selected($filters['sort'] === 'latest')>الأحدث</option>
                                <option value="price_asc" @selected($filters['sort'] === 'price_asc')>السعر: الأقل أولًا</option>
                                <option value="price_desc" @selected($filters['sort'] === 'price_desc')>السعر: الأعلى أولًا</option>
                                <option value="rating" @selected($filters['sort'] === 'rating')>الأعلى تقييمًا</option>
                                <option value="popular" @selected($filters['sort'] === 'popular')>الأكثر مبيعًا</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-brand" type="submit">تطبيق الفلاتر</button>
                            <a class="btn btn-light" href="{{ $storeSearchUrl }}">إعادة الضبط</a>
                        </div>
                    </div>
                </form>
            </aside>

            <div class="col-lg-9">
                @if ($products->isEmpty())
                    <div class="dashboard-card card border-0">
                        <div class="card-body text-center p-5">
                            <div class="fs-1 mb-3">🔍</div>
                            <h2 class="h4 fw-bold">لا توجد نتائج مطابقة</h2>
                            <p class="text-muted-custom mb-4">جرّب تعديل كلمة البحث أو إزالة بعض الفلاتر.</p>
                            <a class="btn btn-brand" href="{{ $storeSearchUrl }}">عرض كل المنتجات</a>
                        </div>
                    </div>
                @else
                    <div class="row g-4">
                        @foreach ($products as $product)
                            <div class="col-md-6 col-xl-4">
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
                                        <div class="d-flex flex-wrap gap-1 mb-2">
                                            @foreach ($product->sub_categories->take(2) as $subCategory)
                                                <span class="badge text-bg-light border text-dark">{{ $subCategory->title }}</span>
                                            @endforeach
                                            @if ($product->rating_count > 0)
                                                <span class="badge text-bg-warning">★ {{ number_format($product->rating_avg, 1) }}</span>
                                            @endif
                                        </div>
                                        <h2 class="h5 fw-bold mb-2">
                                            <a class="text-dark text-decoration-none stretched-link"
                                                href="{{ $productUrl($product) }}">{{ $product->title }}</a>
                                        </h2>
                                        <p class="text-muted-custom small mb-3">{{ Str::limit($product->description, 80) }}</p>

                                        <div class="d-flex align-items-end justify-content-between gap-2 mt-auto position-relative z-2">
                                            <div>
                                                @if ($product->discount_amount > 0)
                                                    <small class="text-muted-custom text-decoration-line-through d-block">
                                                        {{ number_format($product->price, 2) }} جنيه
                                                    </small>
                                                @endif
                                                <strong class="text-brand">
                                                    {{ number_format($product->price_after_discount, 2) }} جنيه
                                                </strong>
                                            </div>
                                            <a class="btn btn-brand" href="{{ $productUrl($product) }}">عرض</a>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
