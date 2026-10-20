@extends('layouts.app')

@section('title', 'حسابي | Printore')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="dashboard-card card border-0 overflow-hidden">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                            <div>
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis mb-3">تم تسجيل
                                    الدخول</span>
                                <h1 class="fw-bold mb-2">أهلاً، {{ $user->first_name }}!</h1>
                                <p class="text-muted-custom mb-0">دي نقطة البداية لمنطقة حسابك في مشروع الويب.</p>
                            </div>

                            <div class="brand-mark"
                                style="width: 5rem; height: 5rem; border-radius: 1.4rem; font-size: 1.7rem;">
                                {{ mb_strtoupper(mb_substr($user->first_name, 0, 1)) }}
                            </div>
                        </div>

                        <hr class="my-5">

                        <div
                            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                            <div>
                                <h2 class="h5 fw-bold mb-1">عناوين الشحن</h2>
                                <p class="text-muted-custom mb-0">أضف عناوينك وحدد العنوان الافتراضي للطلبات.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @if ($user->hasRole(\App\Enums\RoleName::USER))
                                    <a class="btn btn-brand px-4" href="{{ route('orders.index') }}">طلباتي</a>
                                @endif
                                <a class="btn btn-outline-primary px-4" href="{{ route('addresses.index') }}">عناويني</a>
                            </div>
                        </div>

                        @if ($user->hasRole(\App\Enums\RoleName::SELLER))
                            <div
                                class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                                <div>
                                    <h2 class="h5 fw-bold mb-1">إدارة المنتجات</h2>
                                    <p class="text-muted-custom mb-0">أضف منتجاتك وتابع المنتجات المنشورة.</p>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-outline-dark px-4" href="{{ route('stores.show', $user) }}"
                                        target="_blank" rel="noopener">عرض متجري</a>
                                    <a class="btn btn-brand px-4" href="{{ route('seller.products.index') }}">منتجاتي</a>
                                    <a class="btn btn-outline-primary px-4"
                                        href="{{ route('seller.coupons.index') }}">كوبوناتي</a>
                                    <a class="btn btn-outline-primary px-4"
                                        href="{{ route('seller.orders.index') }}">طلبات المنتجات</a>
                                    <a class="btn btn-outline-primary px-4"
                                        href="{{ route('seller.stripe-connect.show') }}">إعداد المدفوعات</a>
                                </div>
                            </div>
                        @endif

                        @if ($user->hasRole(\App\Enums\RoleName::ADMIN))
                            <div
                                class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                                <div>
                                    <h2 class="h5 fw-bold mb-1">إدارة كتالوج المتجر</h2>
                                    <p class="text-muted-custom mb-0">أنشئ التصنيفات التي سيستخدمها البائعون في منتجاتهم.</p>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-outline-primary px-4" href="{{ route('admin.users.roles.index') }}">
                                        إدارة الصلاحيات
                                    </a>
                                    <a class="btn btn-brand px-4" href="{{ route('admin.categories.index') }}">إدارة
                                        التصنيفات</a>
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="rounded-4 bg-light p-3 h-100">
                                    <small class="text-muted-custom d-block mb-1">الاسم</small>
                                    <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="rounded-4 bg-light p-3 h-100">
                                    <small class="text-muted-custom d-block mb-1">البريد الإلكتروني</small>
                                    <strong>{{ $user->email }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="rounded-4 bg-light p-3 h-100">
                                    <small class="text-muted-custom d-block mb-2">صلاحيات الحساب</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($user->roles as $role)
                                            <span class="badge text-bg-primary">{{ $role->label }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
