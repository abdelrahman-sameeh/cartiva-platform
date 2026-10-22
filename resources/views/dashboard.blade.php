@extends('layouts.app')

@section('title', 'حسابي | ' . config('app.name'))

@section('content')
    <div class="container account-dashboard py-4 py-lg-5">
        <header class="account-header">
            <div class="account-identity">
                <span class="account-avatar" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr($user->first_name, 0, 1)) }}
                </span>
                <div>
                    <span class="account-kicker">لوحة التحكم</span>
                    <h1>أهلاً، {{ $user->first_name }}</h1>
                    <p>من هنا تقدر تتابع حسابك وكل الأدوات المتاحة ليك.</p>
                </div>
            </div>

            <div class="account-role-list" aria-label="صلاحيات الحساب">
                @foreach ($user->roles as $role)
                    <span>{{ $role->label }}</span>
                @endforeach
            </div>
        </header>

        <div class="account-layout">
            <main class="account-main">
                <section class="account-panel" aria-labelledby="account-tools-title">
                    <div class="account-panel-heading">
                        <div>
                            <span>حسابي</span>
                            <h2 id="account-tools-title">وصول سريع</h2>
                        </div>
                        <p>أهم الحاجات اللي ممكن تحتاجها في حسابك.</p>
                    </div>

                    <div class="account-action-grid">
                        @if ($user->hasRole(\App\Enums\RoleName::USER))
                            <a class="account-action" href="{{ route('orders.index') }}">
                                <span class="account-action-icon"><i class="bi bi-bag-check" aria-hidden="true"></i></span>
                                <span class="account-action-copy">
                                    <strong>طلباتي</strong>
                                    <small>تابع حالة وتفاصيل طلباتك</small>
                                </span>
                                <i class="bi bi-arrow-left account-action-arrow" aria-hidden="true"></i>
                            </a>

                            <a class="account-action" href="{{ route('cart.index') }}">
                                <span class="account-action-icon"><i class="bi bi-cart3" aria-hidden="true"></i></span>
                                <span class="account-action-copy">
                                    <strong>سلة التسوق</strong>
                                    <small>راجع المنتجات قبل إتمام الطلب</small>
                                </span>
                                <i class="bi bi-arrow-left account-action-arrow" aria-hidden="true"></i>
                            </a>
                        @endif

                        <a class="account-action" href="{{ route('addresses.index') }}">
                            <span class="account-action-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                            <span class="account-action-copy">
                                <strong>عناويني</strong>
                                <small>إدارة عناوين الشحن والعنوان الافتراضي</small>
                            </span>
                            <i class="bi bi-arrow-left account-action-arrow" aria-hidden="true"></i>
                        </a>
                    </div>
                </section>

                @if ($user->hasRole(\App\Enums\RoleName::SELLER))
                    <section class="account-panel" aria-labelledby="seller-tools-title">
                        <div class="account-panel-heading">
                            <div>
                                <span>مساحة البائع</span>
                                <h2 id="seller-tools-title">إدارة متجرك</h2>
                            </div>
                            <a class="account-panel-link" href="{{ route('stores.show', $user) }}" target="_blank"
                                rel="noopener">
                                عرض المتجر
                                <i class="bi bi-box-arrow-up-left" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="account-action-grid">
                            <a class="account-action" href="{{ route('seller.products.index') }}">
                                <span class="account-action-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                                <span class="account-action-copy">
                                    <strong>المنتجات</strong>
                                    <small>أضف المنتجات وعدّل المخزون والاختيارات</small>
                                </span>
                                <i class="bi bi-arrow-left account-action-arrow" aria-hidden="true"></i>
                            </a>

                            <a class="account-action" href="{{ route('seller.orders.index') }}">
                                <span class="account-action-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                                <span class="account-action-copy">
                                    <strong>طلبات المتجر</strong>
                                    <small>تابع الطلبات وحدّث حالة التجهيز</small>
                                </span>
                                <i class="bi bi-arrow-left account-action-arrow" aria-hidden="true"></i>
                            </a>

                            <a class="account-action" href="{{ route('seller.coupons.index') }}">
                                <span class="account-action-icon"><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span>
                                <span class="account-action-copy">
                                    <strong>الكوبونات</strong>
                                    <small>أنشئ عروض خاصة بمنتجات متجرك</small>
                                </span>
                                <i class="bi bi-arrow-left account-action-arrow" aria-hidden="true"></i>
                            </a>

                            <a class="account-action" href="{{ route('seller.stripe-connect.show') }}">
                                <span class="account-action-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
                                <span class="account-action-copy">
                                    <strong>إعداد استلام الأرباح</strong>
                                    <small>راجع حالة حساب Stripe والمتطلبات</small>
                                </span>
                                <i class="bi bi-arrow-left account-action-arrow" aria-hidden="true"></i>
                            </a>
                        </div>
                    </section>
                @endif

                @if ($user->hasRole(\App\Enums\RoleName::ADMIN))
                    <section class="account-panel" aria-labelledby="admin-tools-title">
                        <div class="account-panel-heading">
                            <div>
                                <span>الإدارة</span>
                                <h2 id="admin-tools-title">أدوات المنصة</h2>
                            </div>
                            <p>إدارة البيانات الأساسية وصلاحيات المستخدمين.</p>
                        </div>

                        <div class="account-action-grid">
                            <a class="account-action" href="{{ route('admin.categories.index') }}">
                                <span class="account-action-icon"><i class="bi bi-grid" aria-hidden="true"></i></span>
                                <span class="account-action-copy">
                                    <strong>التصنيفات</strong>
                                    <small>إدارة التصنيفات الرئيسية والفرعية</small>
                                </span>
                                <i class="bi bi-arrow-left account-action-arrow" aria-hidden="true"></i>
                            </a>

                            <a class="account-action" href="{{ route('admin.users.roles.index') }}">
                                <span class="account-action-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                                <span class="account-action-copy">
                                    <strong>الصلاحيات</strong>
                                    <small>راجع أدوار المستخدمين وحدّثها</small>
                                </span>
                                <i class="bi bi-arrow-left account-action-arrow" aria-hidden="true"></i>
                            </a>
                        </div>
                    </section>
                @endif
            </main>

            <aside class="account-sidebar">
                <section class="account-profile-card" aria-labelledby="profile-title">
                    <div class="account-profile-heading">
                        <span><i class="bi bi-person" aria-hidden="true"></i></span>
                        <div>
                            <small>بيانات الحساب</small>
                            <h2 id="profile-title">الملف الشخصي</h2>
                        </div>
                    </div>

                    <dl class="account-details">
                        <div>
                            <dt>الاسم</dt>
                            <dd>{{ $user->first_name }} {{ $user->last_name }}</dd>
                        </div>
                        <div>
                            <dt>البريد الإلكتروني</dt>
                            <dd dir="ltr">{{ $user->email }}</dd>
                        </div>
                        @if ($user->phone)
                            <div>
                                <dt>رقم الهاتف</dt>
                                <dd dir="ltr">{{ $user->phone }}</dd>
                            </div>
                        @endif
                    </dl>

                    <a class="btn btn-light w-100" href="{{ route('addresses.index') }}">
                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        إدارة بيانات الشحن
                    </a>
                </section>

                <div class="account-help-note">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <p><strong>حسابك محمي</strong><span>متشاركش بيانات الدخول أو معلومات الدفع مع أي حد.</span></p>
                </div>
            </aside>
        </div>
    </div>
@endsection
