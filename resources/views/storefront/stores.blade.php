@extends('layouts.app')

@section('title', 'تصفح المتاجر | printore')

@section('content')
    <div class="container pt-3 pb-5">
        <section class="store-directory-hero rounded-5 p-4 p-lg-5 mb-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3 py-2 mb-3">
                        متاجر printore
                    </span>
                    <h1 class="display-5 fw-bold mb-3">اكتشف المتجر المناسب لك</h1>
                    <p class="lead text-muted-custom mb-0">
                        ابحث باسم المتجر، وادخل إلى صفحة البائع لتتصفح منتجاته فقط في مكان واحد.
                    </p>
                </div>
                <div class="col-lg-5">
                    <form method="GET" action="{{ route('stores.index') }}" role="search">
                        <label class="form-label fw-semibold" for="store-search">ابحث باسم المتجر</label>
                        <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden">
                            <input class="form-control border-0 @error('q') is-invalid @enderror" id="store-search" name="q"
                                type="search" value="{{ $query }}" placeholder="مثال: أحمد محمد" maxlength="100">
                            <button class="btn btn-brand px-4" type="submit"><i class="bi bi-search"
                                    aria-hidden="true"></i><span>بحث</span></button>
                        </div>
                        @error('q')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </form>
                </div>
            </div>
        </section>

        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-2 mb-4">
            <div>
                <span class="text-brand fw-semibold">تصفح المتاجر</span>
                <h2 class="fw-bold mb-1">{{ $query !== '' ? 'نتائج البحث عن: ' . $query : 'كل المتاجر' }}</h2>
                <p class="text-muted-custom mb-0">كل متجر يعرض منتجات البائع الخاصة به فقط.</p>
            </div>
            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3 py-2">
                {{ $sellers->total() }} متجر
            </span>
        </div>

        @if ($sellers->isEmpty())
            <div class="dashboard-card card border-0">
                <div class="card-body text-center p-5">
                    <div class="fs-1 mb-3">🏪</div>
                    <h2 class="h4 fw-bold">لم نجد متجرًا بهذا الاسم</h2>
                    <p class="text-muted-custom mb-4">جرّب كتابة جزء من الاسم أو اعرض جميع المتاجر.</p>
                    <a class="btn btn-brand" href="{{ route('stores.index') }}"><i class="bi bi-shop"
                            aria-hidden="true"></i><span>عرض كل المتاجر</span></a>
                </div>
            </div>
        @else
            <div class="row g-4">
                @foreach ($sellers as $seller)
                    @php
                        $sellerName = trim($seller->first_name . ' ' . $seller->last_name);
                        $sellerInitials = mb_strtoupper(
                            mb_substr($seller->first_name, 0, 1) . mb_substr($seller->last_name, 0, 1)
                        );
                    @endphp
                    <div class="col-sm-6 col-lg-4">
                        <article class="store-directory-card dashboard-card card border-0 h-100 position-relative">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <span class="store-avatar">{{ $sellerInitials }}</span>
                                    <div class="min-w-0">
                                        <h3 class="h5 fw-bold mb-1">
                                            <a class="text-dark text-decoration-none stretched-link"
                                                href="{{ route('stores.show', $seller) }}">
                                                متجر {{ $sellerName }}
                                            </a>
                                        </h3>
                                        <span class="text-muted-custom small">
                                            {{ $seller->active_products_count }} منتج متاح
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span class="small text-muted-custom">تسوق من هذا البائع فقط</span>
                                    <span class="btn btn-outline-primary px-3"><i class="bi bi-box-arrow-up-left"
                                            aria-hidden="true"></i><span>زيارة المتجر</span></span>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $sellers->links() }}
            </div>
        @endif
    </div>
@endsection