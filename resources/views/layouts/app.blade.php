<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb">
    <title>@yield('title', config('app.name'))</title>

    <script>
        (() => {
            try {
                const savedTheme = localStorage.getItem('cativa-theme');
                const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.dataset.theme = savedTheme || preferredTheme;
            } catch (error) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body>
    <a class="skip-link" href="#main-content">تخطي إلى المحتوى</a>

    <header class="site-header">
        <nav class="navbar navbar-expand-lg site-navbar" aria-label="التنقل الرئيسي">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}"
                    aria-label="{{ config('app.name') }} - الرئيسية">
                    <span class="brand-mark" aria-hidden="true">C</span>
                    <span class="navbar-brand-copy">
                        <strong>{{ config('app.name') }}</strong>
                        <small>
                            @if (isset($storeSeller) && $storeSeller)
                                متجر {{ trim($storeSeller->first_name . ' ' . $storeSeller->last_name) }}
                            @else
                                Marketplace
                            @endif
                        </small>
                    </span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                    aria-controls="mainNavbar" aria-expanded="false" aria-label="فتح قائمة التنقل">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>

                <div class="collapse navbar-collapse" id="mainNavbar">
                    <div class="navbar-primary-links">
                        <a @class(['nav-link', 'active' => request()->routeIs('home')]) href="{{ route('home') }}"
                            @if (request()->routeIs('home')) aria-current="page" @endif>
                            الرئيسية
                        </a>
                        <a @class(['nav-link', 'active' => request()->routeIs('products.search')])
                            href="{{ route('products.search') }}"
                            @if (request()->routeIs('products.search')) aria-current="page" @endif>
                            المنتجات
                        </a>
                        <a @class(['nav-link', 'active' => request()->routeIs('stores.*')]) href="{{ route('stores.index') }}"
                            @if (request()->routeIs('stores.*')) aria-current="page" @endif>
                            المتاجر
                        </a>
                    </div>

                    <form class="header-search" method="GET" action="{{ route('products.search') }}" role="search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <label class="visually-hidden" for="header-search-query">ابحث في المنتجات</label>
                        <input id="header-search-query" name="q" type="search" value="{{ request('q') }}"
                            placeholder="إيه اللي بتدور عليه؟" maxlength="100">
                        <button type="submit">بحث</button>
                    </form>

                    <div class="navbar-user-actions">
                        <button class="theme-toggle" type="button" data-theme-toggle aria-label="تفعيل الوضع الداكن"
                            title="تغيير مظهر الموقع">
                            <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
                            <span class="theme-toggle-label">الوضع الداكن</span>
                        </button>
                        @auth
                            @if (auth()->user()->hasRole(\App\Enums\RoleName::USER))
                                <a @class(['header-action', 'active' => request()->routeIs('orders.*')])
                                    href="{{ route('orders.index') }}" aria-label="طلباتي">
                                    <i class="bi bi-bag-check" aria-hidden="true"></i>
                                    <span>طلباتي</span>
                                </a>
                                <a @class(['header-action', 'active' => request()->routeIs('cart.*')])
                                    href="{{ route('cart.index') }}" aria-label="سلة التسوق">
                                    <i class="bi bi-cart3" aria-hidden="true"></i>
                                    <span>السلة</span>
                                </a>
                            @endif

                            <div class="dropdown account-menu">
                                <button class="account-menu-trigger dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <span class="user-avatar" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr(auth()->user()->first_name, 0, 1)) }}
                                    </span>
                                    <span class="account-menu-copy">
                                        <small>أهلاً بيك</small>
                                        <strong>{{ auth()->user()->first_name }}</strong>
                                    </span>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li class="account-menu-header">
                                        <strong>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</strong>
                                        <small>{{ auth()->user()->email }}</small>
                                    </li>
                                    <li>
                                        <a @class(['dropdown-item', 'active' => request()->routeIs('dashboard')])
                                            href="{{ route('dashboard') }}">
                                            <i class="bi bi-person" aria-hidden="true"></i>
                                            حسابي
                                        </a>
                                    </li>
                                    <li>
                                        <a @class(['dropdown-item', 'active' => request()->routeIs('addresses.*')])
                                            href="{{ route('addresses.index') }}">
                                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                            عناويني
                                        </a>
                                    </li>

                                    @if (auth()->user()->hasRole(\App\Enums\RoleName::SELLER))
                                        <li><hr class="dropdown-divider"></li>
                                        <li class="dropdown-label">إدارة المتجر</li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('seller.products.index') }}">
                                                <i class="bi bi-box-seam" aria-hidden="true"></i>
                                                المنتجات
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('seller.orders.index') }}">
                                                <i class="bi bi-receipt" aria-hidden="true"></i>
                                                طلبات المتجر
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('seller.stripe-connect.show') }}">
                                                <i class="bi bi-wallet2" aria-hidden="true"></i>
                                                استلام الأرباح
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasRole(\App\Enums\RoleName::ADMIN))
                                        <li><hr class="dropdown-divider"></li>
                                        <li class="dropdown-label">الإدارة</li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.categories.index') }}">
                                                <i class="bi bi-grid" aria-hidden="true"></i>
                                                التصنيفات
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.users.roles.index') }}">
                                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                                الصلاحيات
                                            </a>
                                        </li>
                                    @endif

                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button class="dropdown-item dropdown-logout" type="submit">
                                                <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                                                تسجيل الخروج
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @else
                            <a class="btn btn-light" href="{{ route('login') }}">تسجيل الدخول</a>
                            <a class="btn btn-brand" href="{{ route('register') }}">إنشاء حساب</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    @if (session('success') || session('error'))
        <div class="container system-messages" aria-live="polite">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif
        </div>
    @endif

    <main class="page-shell" id="main-content">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-main">
                <div class="footer-brand">
                    <span class="brand-mark" aria-hidden="true">C</span>
                    <div>
                        <strong>{{ config('app.name') }}</strong>
                        <p>كل احتياجاتك من متاجر مختلفة، في تجربة شراء واحدة بسيطة وآمنة.</p>
                    </div>
                </div>

                <nav class="footer-links" aria-label="روابط سريعة">
                    <a href="{{ route('home') }}">الرئيسية</a>
                    <a href="{{ route('products.search') }}">المنتجات</a>
                    <a href="{{ route('stores.index') }}">المتاجر</a>
                    @guest
                        <a href="{{ route('register') }}">إنشاء حساب</a>
                    @endguest
                </nav>
            </div>

            <div class="footer-bottom">
                <span>© {{ now()->year }} {{ config('app.name') }}. جميع الحقوق محفوظة.</span>
                <span>تجربة تسوق مبنية بعناية.</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
