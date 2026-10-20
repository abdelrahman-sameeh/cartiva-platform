@extends('layouts.app')

@section('title', 'إنشاء حساب | printore')

@section('content')
    <div class="container py-5">
        <div class="auth-card mx-auto" style="max-width: 1040px;">
            <div class="row g-0">
                <div class="col-lg-5 auth-aside p-4 p-lg-5 d-flex align-items-center">
                    <div class="position-relative z-1">
                        <span class="badge rounded-pill text-bg-light text-brand mb-4">ابدأ الآن</span>
                        <h1 class="display-6 fw-bold mb-3">حساب واحد، تجربة تسوق كاملة.</h1>
                        <p class="mb-5 text-white-50">أنشئ حسابك كعميل للتسوق أو كبائع لإضافة منتجاتك وإدارة طلباتك.</p>

                        <div class="d-flex gap-3 align-items-center mb-3">
                            <span class="feature-icon">01</span>
                            <span>أنشئ حسابك خلال دقائق</span>
                        </div>
                        <div class="d-flex gap-3 align-items-center">
                            <span class="feature-icon">02</span>
                            <span>ابدأ التسوق أو البيع مباشرة</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 p-4 p-md-5">
                    <div class="mb-4">
                        <h2 class="fw-bold mb-2">إنشاء حساب جديد</h2>
                        <p class="text-muted-custom mb-0">املأ البيانات التالية للانضمام إلى printore.</p>
                    </div>

                    <form method="POST" action="{{ route('register.store') }}" novalidate>
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="first_name">الاسم الأول</label>
                                <input class="form-control @error('first_name') is-invalid @enderror" id="first_name"
                                    name="first_name" type="text" value="{{ old('first_name') }}" autocomplete="given-name"
                                    required>
                                @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="last_name">اسم العائلة</label>
                                <input class="form-control @error('last_name') is-invalid @enderror" id="last_name"
                                    name="last_name" type="text" value="{{ old('last_name') }}" autocomplete="family-name"
                                    required>
                                @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="email">البريد الإلكتروني</label>
                                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                                    type="email" value="{{ old('email') }}" placeholder="name@example.com"
                                    autocomplete="email" required>
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="password">كلمة المرور</label>
                                <input class="form-control @error('password') is-invalid @enderror" id="password"
                                    name="password" type="password" autocomplete="new-password" required>
                                @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="password_confirmation">تأكيد كلمة المرور</label>
                                <input class="form-control" id="password_confirmation" name="password_confirmation"
                                    type="password" autocomplete="new-password" required>
                            </div>
                        </div>

                        <button class="btn btn-brand w-100 mt-4" type="submit">إنشاء الحساب</button>
                    </form>

                    <p class="text-center text-muted-custom mt-4 mb-0">
                        لديك حساب بالفعل؟
                        <a class="text-brand fw-bold text-decoration-none" href="{{ route('login') }}">سجل الدخول</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection