@extends('layouts.app')

@section('title', 'حاسبة أرباح البائع | ' . config('app.name'))

@section('content')
    @php
        $money = static fn (int $minor): string => number_format($minor / 100, 2);
    @endphp

    <div class="container fee-calculator-page py-4 py-lg-5">
        <header class="fee-calculator-header">
            <div>
                <span class="fee-calculator-kicker">أداة تسعير للبائع</span>
                <h1>اعرف صافي أرباحك قبل إضافة المنتج</h1>
                <p>احسب عمولة Cartiva ورسوم Stripe المتوقعة، وشوف المبلغ اللي هيوصلك وربحك بعد تكلفة المنتج.</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('seller.products.create') }}">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                إضافة منتج
            </a>
        </header>

        <div class="fee-calculator-layout">
            <section class="fee-calculator-panel" aria-labelledby="calculator-inputs-title">
                <div class="fee-calculator-panel-heading">
                    <span>01</span>
                    <div>
                        <h2 id="calculator-inputs-title">بيانات المنتج</h2>
                        <p>اكتب السعر اللي العميل هيدفعه وتكلفة المنتج عليك.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('seller.fee-calculator') }}" class="fee-calculator-form">
                    <div>
                        <label class="form-label" for="price">سعر بيع القطعة</label>
                        <div class="input-group">
                            <input class="form-control @error('price') is-invalid @enderror" id="price" name="price"
                                type="number" min="0.01" max="9999999.99" step="0.01" required
                                value="{{ old('price', request('price')) }}" placeholder="1000.00">
                            <span class="input-group-text">جنيه</span>
                        </div>
                        @error('price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label" for="cost">
                            تكلفة القطعة عليك
                            <span class="text-muted-custom fw-normal">(اختياري)</span>
                        </label>
                        <div class="input-group">
                            <input class="form-control @error('cost') is-invalid @enderror" id="cost" name="cost"
                                type="number" min="0" max="9999999.99" step="0.01"
                                value="{{ old('cost', request('cost')) }}" placeholder="سيبها فاضية لحساب صافي الاستلام فقط">
                            <span class="input-group-text">جنيه</span>
                        </div>
                        @error('cost')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label" for="quantity">الكمية المتوقعة</label>
                        <input class="form-control @error('quantity') is-invalid @enderror" id="quantity"
                            name="quantity" type="number" min="1" max="10000" required
                            value="{{ old('quantity', request('quantity', 1)) }}">
                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label" for="usd_egp_rate">سعر الدولار للتقدير</label>
                        <div class="input-group">
                            <input class="form-control @error('usd_egp_rate') is-invalid @enderror" id="usd_egp_rate"
                                name="usd_egp_rate" type="number" min="1" max="1000" step="0.0001" required
                                value="{{ old('usd_egp_rate', request('usd_egp_rate', $defaultUsdEgpRate)) }}">
                            <span class="input-group-text">EGP</span>
                        </div>
                        @error('usd_egp_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <label class="fee-calculator-check">
                        <input class="form-check-input" name="international_card" type="checkbox" value="1"
                            @checked(request()->boolean('international_card'))>
                        <span>
                            <strong>توقع الدفع ببطاقة دولية</strong>
                            <small>يضيف تقدير 1.5% لرسوم البطاقة الدولية.</small>
                        </span>
                    </label>

                    <button class="btn btn-brand" type="submit">
                        <i class="bi bi-calculator" aria-hidden="true"></i>
                        احسب صافي الربح
                    </button>
                </form>
            </section>

            <section class="fee-calculator-results" aria-labelledby="calculator-result-title">
                <div class="fee-calculator-panel-heading">
                    <span>02</span>
                    <div>
                        <h2 id="calculator-result-title">تفاصيل التسعير</h2>
                        <p>الأرقام بالجنيه المصري وتشمل كل الكمية.</p>
                    </div>
                </div>

                @if ($result)
                    <div class="fee-result-primary">
                        @if ($result['profit_minor'] !== null)
                            <small>ربحك المتوقع بعد تكلفة المنتج</small>
                            <strong class="{{ $result['profit_minor'] < 0 ? 'is-negative' : '' }}"
                                data-testid="estimated-profit">{{ $money($result['profit_minor']) }} جنيه</strong>
                            <span>هامش الربح {{ number_format($result['profit_margin'], 1) }}%</span>
                        @else
                            <small>صافي المبلغ المتوقع استلامه</small>
                            <strong data-testid="estimated-net-only">{{ $money($result['net_payout_minor']) }} جنيه</strong>
                            <span>أضف تكلفة المنتج لو عايز تعرف ربحك الفعلي.</span>
                        @endif
                    </div>

                    <dl class="fee-result-list">
                        <div>
                            <dt>إجمالي المبيعات</dt>
                            <dd>{{ $money($result['gross_amount_minor']) }} جنيه</dd>
                        </div>
                        <div>
                            <dt>رسوم Stripe المتوقعة</dt>
                            <dd data-testid="estimated-stripe-fee">- {{ $money($result['stripe_fee_minor']) }} جنيه</dd>
                        </div>
                        <div>
                            <dt>عمولة Cartiva ({{ number_format($result['platform_fee_percent'], 1) }}%)</dt>
                            <dd data-testid="platform-fee">- {{ $money($result['platform_fee_minor']) }} جنيه</dd>
                        </div>
                        <div class="fee-result-net">
                            <dt>صافي المبلغ المحول لك</dt>
                            <dd data-testid="net-payout">{{ $money($result['net_payout_minor']) }} جنيه</dd>
                        </div>
                        @if ($result['cost_amount_minor'] !== null)
                            <div>
                                <dt>إجمالي تكلفة المنتجات</dt>
                                <dd>- {{ $money($result['cost_amount_minor']) }} جنيه</dd>
                            </div>
                        @endif
                    </dl>
                @else
                    <div class="fee-result-empty">
                        <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                        <h3>ابدأ بسعر المنتج</h3>
                        <p>النتيجة هتوضح لك الرسوم وصافي التحويل والربح المتوقع قبل نشر المنتج.</p>
                    </div>
                @endif

                <div class="fee-calculator-note">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <p>
                        <strong>الحسبة تقديرية قبل الدفع.</strong>
                        رسوم Stripe النهائية تعتمد على البطاقة وسعر التحويل وقت العملية، وبعد الدفع نخصم الرسوم الفعلية فقط.
                    </p>
                </div>
            </section>
        </div>
    </div>
@endsection
