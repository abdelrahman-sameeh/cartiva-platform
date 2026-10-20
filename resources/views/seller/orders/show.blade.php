@extends('layouts.app')

@section('title', 'طلب المنتجات #' . $subOrder->id . ' | printore')

@section('content')
  <div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
      <div>
        <a class="text-brand text-decoration-none fw-semibold" href="{{ route('seller.orders.index') }}">العودة
          للطلبات</a>
        <h1 class="fw-bold mt-2 mb-1">طلب المنتجات #{{ $subOrder->id }}</h1>
        <p class="text-muted-custom mb-0">ضمن الطلب الرئيسي #{{ $subOrder->order_id }}</p>
      </div>
      @include('orders._status', ['status' => $subOrder->status])
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

    <div class="row g-4 align-items-start">
      <div class="col-lg-8">
        <div class="dashboard-card card border-0">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-4">المنتجات</h2>
            @foreach ($subOrder->items as $item)
              <article class="d-flex align-items-center gap-3 border-top py-3">
                @if ($item->cover_image)
                  <img class="rounded-3" style="width: 72px; height: 72px; object-fit: cover;"
                    src="{{ url($item->cover_image) }}" alt="{{ $item->title }}">
                @endif
                <div class="flex-grow-1">
                  <h3 class="h6 fw-bold mb-1">{{ $item->title }}</h3>
                  <small class="text-muted-custom d-block">المقاس: {{ $item->size }} — اللون: {{ $item->color }}</small>
                  <span class="text-muted-custom">الكمية: {{ $item->quantity }}</span>
                </div>
                <strong>{{ number_format((float) $item->price_at_purchase * $item->quantity, 2) }} جنيه</strong>
              </article>
            @endforeach

            <div class="d-flex flex-wrap justify-content-end gap-4 pt-3 border-top">
              <span>الإجمالي: <strong>{{ number_format((float) $subOrder->subtotal, 2) }} جنيه</strong></span>
              <span>الخصم: <strong class="text-success">{{ number_format((float) $subOrder->discount, 2) }}
                  جنيه</strong></span>
              <span>المطلوب: <strong class="text-brand">{{ number_format((float) $subOrder->total_price, 2) }}
                  جنيه</strong></span>
            </div>
          </div>
        </div>
      </div>

      <aside class="col-lg-4">
        <div class="dashboard-card card border-0 mb-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">بيانات العميل</h2>
            <p class="mb-2"><strong>الاسم:</strong> {{ $subOrder->order->user->first_name }}
              {{ $subOrder->order->user->last_name }}</p>
            <p class="mb-2"><strong>الهاتف:</strong> {{ $subOrder->order->phone }}</p>
            @if ($subOrder->order->address)
              <p class="mb-0"><strong>العنوان:</strong> {{ $subOrder->order->address->city }}،
                {{ $subOrder->order->address->street }}</p>
            @endif
          </div>
        </div>

        @php
          $paymentMethodLabels = [
            'cash' => 'الدفع عند الاستلام',
            'card' => 'بطاقة بنكية',
            'wallet' => 'محفظة إلكترونية',
          ];
        @endphp

        <div class="dashboard-card card border-0 mb-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">بيانات الدفع</h2>
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
              <span class="text-muted-custom">حالة الدفع</span>
              @include('orders._payment_status', ['status' => $subOrder->order->payment_status])
            </div>
            <div class="d-flex justify-content-between align-items-center gap-3">
              <span class="text-muted-custom">طريقة الدفع</span>
              <strong>{{ $paymentMethodLabels[$subOrder->order->payment_method] ?? $subOrder->order->payment_method }}</strong>
            </div>
          </div>
        </div>

        <div class="dashboard-card card border-0">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">تحديث الحالة</h2>
            <form method="POST" action="{{ route('seller.orders.status.update', $subOrder) }}">
              @csrf
              @method('PUT')
              <label class="form-label fw-semibold" for="status">الحالة الجديدة</label>
              <select class="form-select" id="status" name="status" required>
                <option value="processing" @selected($subOrder->status === 'processing')>قيد التجهيز</option>
                <option value="shipped" @selected($subOrder->status === 'shipped')>تم الشحن</option>
                <option value="completed" @selected($subOrder->status === 'completed')>مكتمل</option>
                <option value="cancelled" @selected($subOrder->status === 'cancelled')>ملغي</option>
              </select>
              <button class="btn btn-brand w-100 mt-3" type="submit">حفظ الحالة</button>
            </form>
          </div>
        </div>
      </aside>
    </div>
  </div>
@endsection