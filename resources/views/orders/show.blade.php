@extends('layouts.app')

@section('title', 'الطلب #' . $order->id . ' | printore')

@section('content')
  @php
    $paymentMethods = ['cash' => 'الدفع عند الاستلام', 'card' => 'بطاقة بنكية', 'wallet' => 'محفظة إلكترونية'];
    $paymentStatuses = ['pending' => 'في انتظار الدفع', 'paid' => 'مدفوع', 'failed' => 'فشل الدفع', 'refunded' => 'تم رد المبلغ'];
  @endphp

  <div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
      <div>
        <a class="text-brand text-decoration-none fw-semibold" href="{{ route('orders.index') }}">العودة لطلباتي</a>
        <h1 class="fw-bold mt-2 mb-1">الطلب #{{ $order->id }}</h1>
        <p class="text-muted-custom mb-0">تم إنشاؤه في {{ $order->created_at->format('Y-m-d H:i') }}</p>
      </div>
      @include('orders._status', ['status' => $order->status])
    </div>

    <div class="row g-4 align-items-start">
      <div class="col-lg-8">
        @foreach ($order->subOrders as $subOrder)
          <section class="dashboard-card card border-0 mb-4">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                <div>
                  <h2 class="h5 fw-bold mb-1">{{ $subOrder->seller->first_name }} {{ $subOrder->seller->last_name }}</h2>
                  <small class="text-muted-custom">طلب البائع #{{ $subOrder->id }}</small>
                </div>
                @include('orders._status', ['status' => $subOrder->status])
              </div>

              @foreach ($subOrder->items as $item)
                <article class="d-flex align-items-center gap-3 border-top py-3">
                  @if ($item->cover_image)
                    <img class="rounded-3" style="width: 72px; height: 72px; object-fit: cover;"
                      src="{{ url($item->cover_image) }}" alt="{{ $item->title }}">
                  @endif
                  <div class="flex-grow-1">
                    <h3 class="h6 fw-bold mb-1">{{ $item->title }}</h3>
                    <small class="text-muted-custom d-block">المقاس: {{ $item->size }} — اللون: {{ $item->color }}</small>
                    <span class="text-muted-custom">{{ $item->quantity }} ×
                      {{ number_format((float) $item->price_at_purchase, 2) }} جنيه</span>
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
          </section>
        @endforeach
      </div>

      <aside class="col-lg-4">
        <div class="dashboard-card card border-0 mb-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">بيانات الشحن</h2>
            <p class="mb-2"><strong>الهاتف:</strong> {{ $order->phone }}</p>
            @if ($order->address)
              <p class="mb-0"><strong>العنوان:</strong> {{ $order->address->city }}، {{ $order->address->street }}</p>
            @else
              <p class="text-muted-custom mb-0">لم يتم تحديد عنوان.</p>
            @endif
          </div>
        </div>

        <div class="dashboard-card card border-0">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">الدفع والإجمالي</h2>
            <div class="d-flex justify-content-between mb-3">
              <span>طريقة الدفع</span>
              <strong>{{ $paymentMethods[$order->payment_method] ?? $order->payment_method }}</strong>
            </div>
            <div class="d-flex justify-content-between mb-3">
              <span>حالة الدفع</span>
              <strong>{{ $paymentStatuses[$order->payment_status] ?? $order->payment_status }}</strong>
            </div>
            <div class="d-flex justify-content-between mb-3">
              <span>قبل الخصم</span>
              <strong>{{ number_format((float) $order->subtotal, 2) }} جنيه</strong>
            </div>
            <div class="d-flex justify-content-between mb-3">
              <span>الخصم</span>
              <strong class="text-success">{{ number_format((float) $order->discount, 2) }} جنيه</strong>
            </div>
            <div class="d-flex justify-content-between fs-5 pt-3 border-top">
              <span class="fw-bold">الإجمالي</span>
              <strong class="text-brand">{{ number_format((float) $order->total_price, 2) }} جنيه</strong>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
@endsection