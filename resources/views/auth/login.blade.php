@extends('layouts.app')

@section('title', 'تسجيل الدخول | ' . config('app.name'))

@section('content')
    <div class="container py-5">
        <div class="auth-card mx-auto" style="max-width: 980px;">
            <div class="row g-0">
                <div class="col-lg-5 auth-aside p-4 p-lg-5 d-flex align-items-center">
                    <div class="position-relative z-1">
                        <span class="badge rounded-pill text-bg-light text-brand mb-4">أهلاً بعودتك</span>
                        <h1 class="display-6 fw-bold mb-3">كل مشترياتك في مكان واحد.</h1>
                        <p class="mb-5 text-white-50">ادخل إلى حسابك لمتابعة طلباتك وإدارة سلة التسوق بسهولة.</p>

                        <div class="d-flex gap-3 align-items-center mb-3">
                            <span class="feature-icon">✓</span>
                            <span>وصول سريع وآمن إلى حسابك</span>
                        </div>
                        <div class="d-flex gap-3 align-items-center">
                            <span class="feature-icon">✓</span>
                            <span>متابعة الطلبات من كل البائعين</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 p-4 p-md-5">
                    <div class="mb-4">
                        <h2 class="fw-bold mb-2">تسجيل الدخول</h2>
                        <p class="text-muted-custom mb-0">اكتب بيانات حسابك للمتابعة.</p>
                    </div>

                    <form method="POST" action="{{ route('login.store') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="email">البريد الإلكتروني</label>
                            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                                type="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email"
                                autofocus required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="password">كلمة المرور</label>
                            <input class="form-control @error('password') is-invalid @enderror" id="password"
                                name="password" type="password" placeholder="••••••••" autocomplete="current-password"
                                required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1"
                                @checked(old('remember'))>
                            <label class="form-check-label" for="remember">تذكرني</label>
                        </div>

                        <button class="btn btn-brand w-100" type="submit">دخول إلى حسابي</button>
                    </form>

                    <p class="text-center text-muted-custom mt-4 mb-0">
                        ليس لديك حساب؟
                        <a class="text-brand fw-bold text-decoration-none" href="{{ route('register') }}">أنشئ حساباً
                            جديداً</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
