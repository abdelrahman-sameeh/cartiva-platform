@extends('layouts.app')

@section('title', 'طلبات المنتجات | printore')

@section('content')
  <div class="container py-5">
    <div class="mb-4">
      <a class="text-brand text-decoration-none fw-semibold" href="{{ route('dashboard') }}">العودة للحساب</a>
      <h1 class="fw-bold mt-2 mb-1">طلبات المنتجات</h1>
      <p class="text-muted-custom mb-0">تابع طلبات منتجاتك وحدّث حالة تجهيزها وشحنها.</p>
    </div>

    @if ($subOrders->isEmpty())
      <div class="dashboard-card card border-0">
        <div class="card-body text-center p-5">
          <div class="fs-1 mb-3">📋</div>
          <h2 class="h4 fw-bold">لا توجد طلبات بعد</h2>
          <p class="text-muted-custom mb-0">ستظهر هنا الطلبات التي تحتوي على منتجاتك.</p>
        </div>
      </div>
    @else
      <div class="dashboard-card card border-0 overflow-hidden">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="p-3">رقم الطلب</th>
                <th class="p-3">العميل</th>
                <th class="p-3">المنتجات</th>
                <th class="p-3">الإجمالي</th>
                <th class="p-3">الحالة</th>
                <th class="p-3">حالة الدفع</th>
                <th class="p-3 text-end">التفاصيل</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($subOrders as $subOrder)
                <tr>
                  <td class="p-3 fw-bold">#{{ $subOrder->id }}</td>
                  <td class="p-3">{{ $subOrder->order->user->first_name }} {{ $subOrder->order->user->last_name }}</td>
                  <td class="p-3">{{ $subOrder->items->sum('quantity') }}</td>
                  <td class="p-3 fw-semibold">{{ number_format((float) $subOrder->total_price, 2) }} جنيه</td>
                  <td class="p-3">@include('orders._status', ['status' => $subOrder->status])</td>
                  <td class="p-3">
                    @include('orders._payment_status', ['status' => $subOrder->order->payment_status])
                  </td>
                  <td class="p-3 text-end">
                    <a class="btn btn-outline-primary" href="{{ route('seller.orders.show', $subOrder) }}">عرض</a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-4">{{ $subOrders->links() }}</div>
    @endif
  </div>
@endsection