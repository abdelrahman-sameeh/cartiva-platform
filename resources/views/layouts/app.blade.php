<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --brand: #4f46e5;
            --brand-dark: #312e81;
            --brand-bright: #7c3aed;
            --accent: #f97316;
            --ink: #172033;
            --muted: #667085;
            --brand-soft: #eef2ff;
            --danger: #d92d20;
            --danger-dark: #b42318;
            --danger-soft: #fee4e2;
            --neutral-soft: #f2f4f7;
        }

        body {
            min-height: 100vh;
            padding-top: 5.25rem;
            color: var(--ink);
            font-family: "Readex Pro Variable", sans-serif;
            background:
                radial-gradient(circle at 10% 10%, rgba(79, 70, 229, .13), transparent 32rem),
                radial-gradient(circle at 90% 90%, rgba(249, 115, 22, .1), transparent 30rem),
                #f8f9fc;
        }

        .navbar-brand {
            color: var(--ink);
            font-weight: 800;
            letter-spacing: -.04em;
        }

        .site-navbar {
            z-index: 1030;
            background: rgba(255, 255, 255, .94) !important;
            box-shadow: 0 .35rem 1.25rem rgba(16, 24, 40, .06);
            backdrop-filter: blur(14px);
        }

        .site-navbar .navbar-toggler {
            width: 2.85rem;
            height: 2.85rem;
            padding: .5rem;
            border-color: #e4e7ec;
            border-radius: .75rem;
            box-shadow: none;
        }

        .site-navbar .navbar-toggler:focus-visible {
            outline: 0;
            box-shadow: 0 0 0 .25rem rgba(91, 76, 240, .18);
        }

        .navbar-actions {
            margin-inline-start: auto;
            padding: .3rem;
            border: 1px solid rgba(16, 24, 40, .06);
            border-radius: 1rem;
            background: rgba(248, 249, 252, .86);
        }

        .site-navbar .navbar-actions .btn {
            min-height: 2.65rem;
            padding: .55rem .9rem !important;
            border-color: transparent;
            border-radius: .75rem;
            box-shadow: none;
            white-space: nowrap;
        }

        .site-navbar .navbar-actions .btn:hover {
            transform: none;
        }

        .site-navbar .navbar-actions .btn-light {
            color: var(--muted);
            background: transparent;
        }

        .site-navbar .navbar-actions .btn-light:hover,
        .site-navbar .navbar-actions .btn-light:focus {
            color: var(--brand-dark);
            border-color: rgba(91, 76, 240, .12);
            background: var(--brand-soft);
        }

        .site-navbar .navbar-actions .btn-brand {
            border-color: transparent;
            background: linear-gradient(135deg, var(--brand), var(--brand-bright));
            box-shadow: 0 .4rem 1rem rgba(79, 70, 229, .24);
        }

        .site-navbar .navbar-actions .btn-outline-primary {
            color: var(--brand-dark);
            border-color: rgba(91, 76, 240, .12);
            background: var(--brand-soft);
        }

        .site-navbar .navbar-actions .btn-outline-danger {
            color: var(--danger-dark);
            border-color: transparent;
            background: transparent;
        }

        .site-navbar .navbar-actions .btn-outline-danger:hover,
        .site-navbar .navbar-actions .btn-outline-danger:focus {
            color: #fff;
            border-color: var(--danger);
            background: var(--danger);
        }

        .navbar-desktop-actions {
            margin-inline-start: auto;
        }

        .navbar-desktop-home {
            min-height: 2.85rem;
            padding: .65rem 1.1rem;
            color: var(--muted);
            border: 1px solid rgba(16, 24, 40, .07);
            border-radius: .9rem;
            background: rgba(248, 249, 252, .9);
            box-shadow: none;
        }

        .navbar-desktop-home:hover,
        .navbar-desktop-home:focus,
        .navbar-desktop-home.is-active {
            color: var(--brand-dark);
            border-color: rgba(91, 76, 240, .14);
            background: var(--brand-soft);
            transform: none;
        }

        .navbar-menu-trigger {
            min-width: 8rem;
            min-height: 2.85rem;
            border: 0;
            border-radius: .9rem;
            background: linear-gradient(135deg, var(--brand), var(--brand-bright));
            box-shadow: 0 .5rem 1.25rem rgba(79, 70, 229, .24);
        }

        .navbar-menu-trigger:hover,
        .navbar-menu-trigger:focus {
            color: #fff;
            background: linear-gradient(135deg, var(--brand-dark), var(--brand));
        }

        .navbar-menu-dropdown .dropdown-menu {
            min-width: 15rem;
            padding: .6rem;
            border: 1px solid rgba(16, 24, 40, .08);
            border-radius: 1rem;
            background: rgba(255, 255, 255, .98);
            box-shadow: 0 1rem 2.5rem rgba(16, 24, 40, .14);
        }

        .navbar-menu-dropdown .dropdown-item {
            display: flex;
            min-height: 2.7rem;
            padding: .65rem .85rem;
            color: var(--ink);
            font-weight: 650;
            border-radius: .7rem;
            align-items: center;
            gap: .65rem;
        }

        .navbar-menu-dropdown .dropdown-item .bi {
            width: 1.2rem;
            flex: 0 0 auto;
            font-size: 1.05rem;
            text-align: center;
        }

        .navbar-menu-dropdown .dropdown-item:hover,
        .navbar-menu-dropdown .dropdown-item:focus {
            color: var(--brand-dark);
            background: var(--brand-soft);
        }

        .navbar-menu-dropdown .dropdown-item.active {
            color: #fff;
            background: linear-gradient(135deg, var(--brand), var(--brand-bright));
        }

        .navbar-menu-dropdown .dropdown-logout {
            width: 100%;
            color: var(--danger-dark);
            border: 0;
            background: transparent;
            text-align: start;
        }

        .navbar-menu-dropdown .dropdown-logout:hover,
        .navbar-menu-dropdown .dropdown-logout:focus {
            color: #fff;
            background: var(--danger);
        }

        .navbar-brand {
            min-width: 0;
        }

        .navbar-brand-name {
            min-width: 0;
            line-height: 1.25;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .navbar-brand-name strong {
            display: block;
            font-size: 1.1rem;
            letter-spacing: -.03em;
        }

        .navbar-brand-context {
            display: block;
            color: var(--muted);
            font-size: .68rem;
            font-weight: 600;
            letter-spacing: 0;
        }

        @media (max-width: 991.98px) {
            .navbar-brand {
                max-width: calc(100% - 4rem);
            }
        }

        .brand-mark {
            position: relative;
            display: inline-grid;
            width: 2.25rem;
            height: 2.25rem;
            margin-left: .55rem;
            color: #fff;
            place-items: center;
            border-radius: .75rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--brand-dark), var(--brand-bright));
            box-shadow: 0 .5rem 1.25rem rgba(79, 70, 229, .28);
        }

        .brand-mark::after {
            position: absolute;
            width: .55rem;
            height: .55rem;
            right: -.12rem;
            bottom: -.12rem;
            content: "";
            border: 2px solid #fff;
            border-radius: 50%;
            background: var(--accent);
        }

        .page-shell {
            min-height: calc(100vh - 73px);
        }

        @media (max-width: 991.98px) {
            .site-navbar .navbar-collapse {
                max-height: calc(100vh - 5.25rem);
                overflow-y: auto;
                border-top: 1px solid #eaecf0;
            }

            .navbar-actions .btn,
            .navbar-actions form,
            .navbar-actions form .btn {
                width: 100%;
            }
        }

        @media (max-width: 359.98px) {
            .navbar-brand {
                font-size: 1rem;
            }

            .brand-mark {
                width: 2rem;
                height: 2rem;
                margin-left: .4rem;
                font-size: .85rem;
            }
        }

        .auth-card {
            overflow: hidden;
            border: 1px solid rgba(16, 24, 40, .07);
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 1.5rem 4rem rgba(16, 24, 40, .1);
            backdrop-filter: blur(12px);
        }

        .auth-aside {
            position: relative;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(145deg, #171331, var(--brand-dark));
        }

        .auth-aside::before,
        .auth-aside::after {
            position: absolute;
            content: "";
            border-radius: 50%;
            background: rgba(255, 255, 255, .09);
        }

        .auth-aside::before {
            width: 18rem;
            height: 18rem;
            top: -8rem;
            left: -7rem;
        }

        .auth-aside::after {
            width: 12rem;
            height: 12rem;
            right: -5rem;
            bottom: -5rem;
        }

        .form-control,
        .form-select {
            min-height: 3.15rem;
            border-color: #e4e7ec;
            border-radius: .85rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(91, 76, 240, .55);
            box-shadow: 0 0 0 .25rem rgba(91, 76, 240, .12);
        }

        .btn {
            display: inline-flex;
            min-height: 3.15rem;
            padding: .7rem 1rem;
            font-weight: 700;
            font-size: .95rem;
            line-height: 1.25;
            border-radius: 1rem;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            box-shadow: 0 .35rem .85rem rgba(16, 24, 40, .08);
            transition: color .2s ease, background-color .2s ease, border-color .2s ease,
                box-shadow .2s ease, transform .2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 .55rem 1rem rgba(16, 24, 40, .12);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 .2rem .5rem rgba(16, 24, 40, .08);
        }

        .btn:focus-visible {
            outline: 0;
            box-shadow: 0 0 0 .25rem rgba(91, 76, 240, .18);
        }

        .btn:disabled,
        .btn.disabled {
            transform: none;
            box-shadow: none;
        }

        .btn-brand,
        .btn-primary {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, var(--brand), var(--brand-bright));
            box-shadow: 0 .55rem 1.2rem rgba(79, 70, 229, .22);
        }

        .btn-brand:hover,
        .btn-brand:focus,
        .btn-primary:hover,
        .btn-primary:focus {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, var(--brand-dark), var(--brand));
            box-shadow: 0 .7rem 1.4rem rgba(49, 46, 129, .25);
        }

        .btn-outline-primary,
        .btn-outline-secondary,
        .btn-light,
        .btn-outline-dark {
            color: var(--brand-dark);
            border-color: rgba(79, 70, 229, .13);
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 .35rem .9rem rgba(23, 32, 51, .07);
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus,
        .btn-light:hover,
        .btn-light:focus,
        .btn-outline-dark:hover,
        .btn-outline-dark:focus {
            color: var(--brand-dark);
            border-color: rgba(79, 70, 229, .28);
            background: var(--brand-soft);
            box-shadow: 0 .55rem 1.1rem rgba(79, 70, 229, .12);
        }

        .btn-danger,
        .btn-outline-danger {
            color: var(--danger-dark);
            border-color: rgba(217, 45, 32, .12);
            background: var(--danger-soft);
            box-shadow: 0 .35rem .9rem rgba(180, 35, 24, .08);
        }

        .btn-danger:hover,
        .btn-danger:focus,
        .btn-outline-danger:hover,
        .btn-outline-danger:focus {
            color: #fff;
            border-color: var(--danger);
            background: var(--danger);
        }

        .text-brand {
            color: var(--brand) !important;
        }

        .text-muted-custom {
            color: var(--muted);
        }

        .feature-icon {
            display: grid;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: .8rem;
            background: rgba(255, 255, 255, .13);
        }

        .dashboard-card {
            border: 1px solid rgba(16, 24, 40, .07);
            border-radius: 1.25rem;
            box-shadow: 0 1rem 2.5rem rgba(16, 24, 40, .07);
        }
    </style>
    @stack('styles')
</head>

<body>
    <nav class="navbar navbar-expand-lg fixed-top site-navbar border-bottom py-3" aria-label="التنقل الرئيسي">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}" aria-label="{{ config('app.name') }} - الرئيسية">
                <span class="brand-mark">
                    C
                </span>
                <span class="navbar-brand-name">
                    <strong>{{ config('app.name') }}</strong>
                    @if (isset($storeSeller) && $storeSeller)
                        <small class="navbar-brand-context">متجر {{ trim($storeSeller->first_name . ' ' . $storeSeller->last_name) }}</small>
                    @else
                        <small class="navbar-brand-context">Marketplace</small>
                    @endif
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="فتح قائمة التنقل">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <div class="navbar-desktop-actions d-none d-lg-flex align-items-center gap-2">
                    <a @class(['btn navbar-desktop-home', 'is-active' => request()->routeIs('home')])
                        href="{{ route('home') }}"
                        @if (request()->routeIs('home')) aria-current="page" @endif>
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span>الرئيسية</span>
                    </a>
                    <div class="dropdown navbar-menu-dropdown">
                    <button class="btn btn-brand dropdown-toggle navbar-menu-trigger" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-grid" aria-hidden="true"></i>
                        <span>القائمة</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a @class(['dropdown-item', 'active' => request()->routeIs('stores.index')])
                                href="{{ route('stores.index') }}">
                                <i class="bi bi-shop" aria-hidden="true"></i>
                                <span>تصفح المتاجر</span>
                            </a>
                        </li>
                        @auth
                            @if (auth()->user()->hasRole(\App\Enums\RoleName::USER))
                                <li>
                                    <a @class(['dropdown-item', 'active' => request()->routeIs('orders.*')])
                                        href="{{ route('orders.index') }}"><i class="bi bi-bag-check" aria-hidden="true"></i><span>طلباتي</span></a>
                                </li>
                                <li>
                                    <a @class(['dropdown-item', 'active' => request()->routeIs('cart.*')])
                                        href="{{ route('cart.index') }}"><i class="bi bi-cart3" aria-hidden="true"></i><span>السلة</span></a>
                                </li>
                            @endif
                            <li>
                                <a @class(['dropdown-item', 'active' => request()->routeIs('dashboard')])
                                    href="{{ route('dashboard') }}"><i class="bi bi-person-circle" aria-hidden="true"></i><span>حسابي</span></a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item dropdown-logout" type="submit"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>تسجيل الخروج</span></button>
                                </form>
                            </li>
                        @else
                            <li>
                                <a @class(['dropdown-item', 'active' => request()->routeIs('login*')])
                                    href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i><span>دخول</span></a>
                            </li>
                            <li>
                                <a @class(['dropdown-item', 'active' => request()->routeIs('register*')])
                                    href="{{ route('register') }}"><i class="bi bi-person-plus" aria-hidden="true"></i><span>حساب جديد</span></a>
                            </li>
                        @endauth
                    </ul>
                    </div>
                </div>

                <div class="navbar-actions d-flex d-lg-none flex-column align-items-stretch gap-2 pt-3">
                    <a
                    @class([
                        'btn px-3',
                        'btn-brand' => request()->routeIs('home'),
                        'btn-light' => !request()->routeIs('home'),
                    ])
                    href="{{ route('home') }}"
                    >
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span>الرئيسية</span>
                    </a>
                    <a @class([
                        'btn px-3',
                        'btn-brand' => request()->routeIs('stores.index'),
                        'btn-light' => !request()->routeIs('stores.index'),
                    ]) href="{{ route('stores.index') }}"
                        @if (request()->routeIs('stores.index')) aria-current="page" @endif>
                        <i class="bi bi-shop" aria-hidden="true"></i>
                        <span>المتاجر</span>
                    </a>
                @auth
                    @if (auth()->user()->hasRole(\App\Enums\RoleName::USER))
                        <a @class(['btn px-3', 'btn-brand' => request()->routeIs('orders.*'), 'btn-light' => !request()->routeIs('orders.*')]) href="{{ route('orders.index') }}"
                            @if (request()->routeIs('orders.*')) aria-current="page" @endif><i class="bi bi-bag-check" aria-hidden="true"></i><span>طلباتي</span></a>
                        <a @class(['btn px-3', 'btn-brand' => request()->routeIs('cart.*'), 'btn-light' => !request()->routeIs('cart.*')]) href="{{ route('cart.index') }}"
                            @if (request()->routeIs('cart.*')) aria-current="page" @endif><i class="bi bi-cart3" aria-hidden="true"></i><span>السلة</span></a>
                    @endif
                    <a @class(['btn px-3', 'btn-brand' => request()->routeIs('dashboard'), 'btn-light' => !request()->routeIs('dashboard')]) href="{{ route('dashboard') }}"
                        @if (request()->routeIs('dashboard')) aria-current="page" @endif><i class="bi bi-person-circle" aria-hidden="true"></i><span>حسابي</span></a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline-danger px-3" type="submit"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>تسجيل الخروج</span></button>
                    </form>
                @else
                    <a @class(['btn px-3', 'btn-brand' => request()->routeIs('login*'), 'btn-light' => !request()->routeIs('login*')]) href="{{ route('login') }}"
                        @if (request()->routeIs('login*')) aria-current="page" @endif><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i><span>دخول</span></a>
                    <a @class(['btn px-3', 'btn-brand' => request()->routeIs('register*'), 'btn-outline-primary' => !request()->routeIs('register*')]) href="{{ route('register') }}"
                        @if (request()->routeIs('register*')) aria-current="page" @endif><i class="bi bi-person-plus" aria-hidden="true"></i><span>حساب جديد</span></a>
                @endauth
                </div>
            </div>
        </div>
    </nav>

    @if (session('success'))
        <div class="container pt-3">
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        </div>
    @endif

    <main class="page-shell">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
