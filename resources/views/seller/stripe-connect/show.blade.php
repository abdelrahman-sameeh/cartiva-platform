@extends('layouts.app')

@section('title', 'إعداد استلام الأرباح | printore')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="dashboard-card card border-0 overflow-hidden">
                    <div class="card-body p-4 p-md-5">
                        <div class="mb-4">
                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis mb-3">Stripe
                                Connect</span>
                            <h1 class="h3 fw-bold mb-2">إعداد استلام الأرباح</h1>
                            <p class="text-muted-custom mb-0">
                                أكمل بيانات نشاطك وحسابك البنكي في صفحة Stripe الآمنة حتى تتمكن من استقبال تحويلات
                                أرباحك من المنصة.
                            </p>
                        </div>

                        @if ($errors->has('stripe'))
                            <div class="alert alert-danger" role="alert">{{ $errors->first('stripe') }}</div>
                        @endif

                        @if (!$stripeAccount)
                            <div class="alert alert-info" role="status">
                                لم تبدأ إعداد حساب المدفوعات بعد.
                            </div>
                        @elseif ($stripeAccount->isReady())
                            <div class="alert alert-success" role="status">
                                حسابك جاهز لاستقبال تحويلات الأرباح.
                            </div>
                        @elseif ($stripeAccount->onboarding_status === 'action_required')
                            <div class="alert alert-warning" role="status">
                                يحتاج حسابك إلى بيانات أو إجراء إضافي قبل تفعيله.
                            </div>
                        @elseif ($stripeAccount->onboarding_status === 'restricted')
                            <div class="alert alert-danger" role="status">
                                حسابك مقيّد حاليًا. افتح إعداد Stripe لمعرفة الإجراء المطلوب.
                            </div>
                        @else
                            <div class="alert alert-info" role="status">
                                حسابك قيد المراجعة أو التفعيل لدى Stripe.
                            </div>
                        @endif

                        @if ($stripeAccount)
                            <div class="row g-3 my-4">
                                <div class="col-md-6">
                                    <div class="rounded-4 bg-light p-3 h-100">
                                        <small class="text-muted-custom d-block mb-2">السحب إلى البنك</small>
                                        <strong>{{ $stripeAccount->payouts_status === 'active' ? 'مفعّل' : 'غير مفعّل' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="rounded-4 bg-light p-3 h-100">
                                        <small class="text-muted-custom d-block mb-2">استلام التحويلات</small>
                                        <strong>{{ $stripeAccount->transfers_status === 'active' ? 'مفعّل' : 'غير مفعّل' }}</strong>
                                    </div>
                                </div>
                            </div>

                            @if ($stripeAccount->hasOutstandingRequirements())
                                <div class="rounded-4 border p-3 mb-4">
                                    <h2 class="h6 fw-bold">خطوات بسيطة متبقية</h2>
                                    <p class="mb-0 text-muted-custom">
                                        سيجمع Stripe البيانات المطلوبة حاليًا فقط: بيانات النشاط الأساسية، والتحقق من
                                        الهوية، وحساب استلام الأرباح.
                                    </p>
                                </div>
                            @endif

                            @if ($stripeAccount->last_synced_at)
                                <p class="small text-muted-custom">
                                    آخر تحديث: {{ $stripeAccount->last_synced_at->format('Y-m-d H:i') }}
                                </p>
                            @endif
                        @endif

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            @if (!$stripeAccount || !$stripeAccount->isReady())
                                <form method="POST" action="{{ route('seller.stripe-connect.onboard') }}">
                                    @csrf
                                    <button class="btn btn-brand px-4" type="submit">
                                        {{ $stripeAccount ? 'استكمال الإعداد في Stripe' : 'بدء الإعداد في Stripe' }}
                                    </button>
                                </form>
                            @endif

                            <a class="btn btn-outline-secondary px-4" href="{{ route('dashboard') }}">العودة إلى حسابي</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
