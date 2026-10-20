@extends('layouts.app')

@section('title', 'كوبوناتي | printore')

@section('content')
  <div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
      <div>
        <a class="text-brand text-decoration-none fw-semibold" href="{{ route('dashboard') }}">العودة للحساب</a>
        <h1 class="fw-bold mt-2 mb-1">كوبوناتي</h1>
        <p class="text-muted-custom mb-0">أنشئ أكواد خصم وحدد مدة وعدد مرات استخدامها.</p>
      </div>
      <a class="btn btn-brand px-4" href="{{ route('seller.coupons.create') }}">إضافة كوبون</a>
    </div>

    @if ($coupons->isEmpty())
      <div class="dashboard-card card border-0">
        <div class="card-body text-center p-5">
          <h2 class="h4 fw-bold">لا توجد كوبونات بعد</h2>
          <p class="text-muted-custom mb-4">أضف أول كوبون لتقديم خصم على منتجاتك.</p>
          <a class="btn btn-brand px-4" href="{{ route('seller.coupons.create') }}">إنشاء أول كوبون</a>
        </div>
      </div>
    @else
      <div class="dashboard-card card border-0 overflow-hidden">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="p-3">الكود</th>
                <th class="p-3">الخصم</th>
                <th class="p-3">الاستخدام</th>
                <th class="p-3">تاريخ الانتهاء</th>
                <th class="p-3">الحالة</th>
                <th class="p-3 text-end">الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($coupons as $coupon)
                <tr>
                  <td class="p-3 fw-bold">{{ $coupon->code }}</td>
                  <td class="p-3">{{ number_format($coupon->percentage, 2) }}%</td>
                  <td class="p-3">{{ $coupon->used_count }} / {{ $coupon->max_usage }}</td>
                  <td class="p-3">{{ $coupon->expire_date->format('Y-m-d') }}</td>
                  <td class="p-3">
                    @if (!$coupon->is_active)
                      <span class="badge text-bg-secondary">متوقف</span>
                    @elseif ($coupon->isExpired())
                      <span class="badge text-bg-danger">منتهي</span>
                    @elseif ($coupon->isExhausted())
                      <span class="badge text-bg-warning">اكتمل الاستخدام</span>
                    @else
                      <span class="badge text-bg-success">فعال</span>
                    @endif
                  </td>
                  <td class="p-3">
                    <div class="d-flex justify-content-end gap-2">
                      <a class="btn btn-outline-primary" href="{{ route('seller.coupons.edit', $coupon) }}">تعديل</a>
                      <form method="POST" action="{{ route('seller.coupons.destroy', $coupon) }}"
                        onsubmit="return confirm('هل أنت متأكد من حذف هذا الكوبون؟')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">حذف</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-4">
        {{ $coupons->links() }}
      </div>
    @endif
  </div>
@endsection