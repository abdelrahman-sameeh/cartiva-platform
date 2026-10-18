@extends('layouts.app')

@section('title', 'إعداد المدفوعات | Printora')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="dashboard-card card border-0 overflow-hidden">
                    <div class="card-body p-4 p-md-5">
                        <div class="mb-4">
                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis mb-3">Stripe Connect</span>
                            <h1 class="h3 fw-bold mb-2">إعداد المدفوعات والتحويلات</h1>
                            <p class="text-muted-custom mb-0">
                                أكمل بيانات نشاطك وحسابك البنكي في صفحة Stripe الآمنة حتى تتمكن من استقبال المدفوعات والأرباح.
                            </p>
                        </div>

                        @if ($errors->has('stripe'))
                            <div class="alert alert-danger" role="alert">{{ $errors->first('stripe') }}</div>
                        @endif

                        @if (! $stripeAccount)
                            <div class="alert alert-info" role="status">
                                لم تبدأ إعداد حساب المدفوعات بعد.
                            </div>
                        @elseif ($stripeAccount->charges_enabled && $stripeAccount->payouts_enabled)
                            <div class="alert alert-success" role="status">
                                حسابك جاهز لاستقبال المدفوعات والتحويلات.
                            </div>
                        @elseif ($stripeAccount->disabled_reason)
                            <div class="alert alert-danger" role="status">
                                يحتاج حسابك إلى إجراء إضافي قبل تفعيله.
                            </div>
                        @elseif ($stripeAccount->details_submitted)
                            <div class="alert alert-warning" role="status">
                                تم إرسال بياناتك، وما زال الحساب قيد المراجعة أو التفعيل.
                            </div>
                        @else
                            <div class="alert alert-info" role="status">
                                إعداد الحساب غير مكتمل. يمكنك المتابعة من حيث توقفت.
                            </div>
                        @endif

                        @if ($stripeAccount)
                            <div class="row g-3 my-4">
                                <div class="col-md-4">
                                    <div class="rounded-4 bg-light p-3 h-100">
                                        <small class="text-muted-custom d-block mb-2">البيانات المطلوبة</small>
                                        <strong>{{ $stripeAccount->details_submitted ? 'مكتملة' : 'غير مكتملة' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="rounded-4 bg-light p-3 h-100">
                                        <small class="text-muted-custom d-block mb-2">استقبال المدفوعات</small>
                                        <strong>{{ $stripeAccount->charges_enabled ? 'مفعّل' : 'غير مفعّل' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="rounded-4 bg-light p-3 h-100">
                                        <small class="text-muted-custom d-block mb-2">استلام التحويلات</small>
                                        <strong>{{ $stripeAccount->payouts_enabled ? 'مفعّل' : 'غير مفعّل' }}</strong>
                                    </div>
                                </div>
                            </div>

                            @if ($stripeAccount->requirements_currently_due)
                                <div class="rounded-4 border p-3 mb-4">
                                    <h2 class="h6 fw-bold">بيانات مطلوبة حاليًا</h2>
                                    <ul class="mb-0">
                                        @foreach ($stripeAccount->requirements_currently_due as $requirement)
                                            <li><code>{{ $requirement }}</code></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($stripeAccount->last_synced_at)
                                <p class="small text-muted-custom">
                                    آخر تحديث: {{ $stripeAccount->last_synced_at->format('Y-m-d H:i') }}
                                </p>
                            @endif
                        @endif

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            @if (! $stripeAccount || ! $stripeAccount->charges_enabled || ! $stripeAccount->payouts_enabled)
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
