@extends('layouts.app')

@section('title', 'إتمام الطلب | printore')

@section('content')
  <div class="container py-5">
    <div class="mb-4">
      <a class="text-brand text-decoration-none fw-semibold" href="{{ route('cart.index') }}">العودة للسلة</a>
      <h1 class="fw-bold mt-2 mb-1">إتمام الطلب</h1>
      <p class="text-muted-custom mb-0">راجع بيانات الشحن وطريقة الدفع قبل تأكيد الطلب.</p>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('orders.store') }}">
      @csrf
      <div class="row g-4 align-items-start">
        <div class="col-lg-7">
          <div class="dashboard-card card border-0 mb-4">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-4">بيانات الشحن</h2>

              <div class="mb-3">
                <label class="form-label fw-semibold" for="phone">رقم الهاتف</label>
                <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="tel"
                  maxlength="15" value="{{ old('phone', auth()->user()->phone) }}" required>
              </div>

              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                  <label class="form-label fw-semibold mb-0" for="address_id">عنوان الشحن</label>
                  <a class="text-brand text-decoration-none fw-semibold" href="{{ route('addresses.create') }}">إضافة
                    عنوان</a>
                </div>
                <select class="form-select @error('address_id') is-invalid @enderror" id="address_id" name="address_id">
                  <option value="">بدون عنوان محدد</option>
                  @foreach ($addresses as $address)
                    <option value="{{ $address->id }}" @selected((string) old('address_id', $addresses->firstWhere('is_default', true)?->id) === (string) $address->id)>
                      {{ $address->city }}، {{ $address->street }}{{ $address->is_default ? ' — الافتراضي' : '' }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div>
                <label class="form-label fw-semibold" for="payment_method">طريقة الدفع</label>
                <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method"
                  name="payment_method" required>
                  <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>الدفع عند الاستلام</option>
                  <option value="card" @selected(old('payment_method') === 'card')>بطاقة بنكية</option>
                  <option value="wallet" @selected(old('payment_method') === 'wallet')>محفظة إلكترونية</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <aside class="col-lg-5">
          <div class="dashboard-card card border-0">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-4">ملخص الطلب</h2>

              @foreach ($cart['groups'] as $group)
                @foreach ($group['items'] as $item)
                  <div class="d-flex justify-content-between gap-3 mb-3">
                    <span>
                      {{ $item['product']['title'] }} × {{ $item['quantity'] }}
                      <small class="text-muted-custom d-block">{{ $item['variant']['size'] }} /
                        {{ $item['variant']['color'] }}</small>
                    </span>
                    <strong>{{ number_format($item['product']['price'] * $item['quantity'], 2) }} جنيه</strong>
                  </div>
                @endforeach
              @endforeach

              <hr>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-muted-custom">الإجمالي</span>
                <strong>{{ number_format($cart['summary_cart']['subtotal'], 2) }} جنيه</strong>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-muted-custom">الخصم</span>
                <strong class="text-success">{{ number_format($cart['summary_cart']['discount'], 2) }} جنيه</strong>
              </div>
              <div class="d-flex justify-content-between fs-5 pt-3 border-top">
                <span class="fw-bold">المطلوب</span>
                <strong class="text-brand">{{ number_format($cart['summary_cart']['total'], 2) }} جنيه</strong>
              </div>

              <button class="btn btn-brand w-100 mt-4" type="submit">تأكيد الطلب</button>
            </div>
          </div>
        </aside>
      </div>
    </form>
  </div>
@endsection