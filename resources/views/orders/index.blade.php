@extends('layouts.app')

@section('title', 'طلباتي | ' . config('app.name'))

@section('content')
  <div class="container py-5">
    <div class="mb-4">
      <a class="text-brand text-decoration-none fw-semibold" href="{{ route('dashboard') }}">العودة للحساب</a>
      <h1 class="fw-bold mt-2 mb-1">طلباتي</h1>
      <p class="text-muted-custom mb-0">تابع حالة طلباتك واعرض تفاصيل كل طلب.</p>
    </div>

    @if ($orders->isEmpty())
      <div class="dashboard-card card border-0">
        <div class="card-body text-center p-5">
          <div class="fs-1 mb-3">📦</div>
          <h2 class="h4 fw-bold">لا توجد طلبات بعد</h2>
          <p class="text-muted-custom mb-4">ابدأ التسوق وسيظهر طلبك هنا بعد تأكيده.</p>
          <a class="btn btn-brand px-4" href="{{ route('products.search') }}">تصفح المنتجات</a>
        </div>
      </div>
    @else
      <div class="dashboard-card card border-0 overflow-hidden">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="p-3">رقم الطلب</th>
                <th class="p-3">التاريخ</th>
                <th class="p-3">البائعون</th>
                <th class="p-3">الإجمالي</th>
                <th class="p-3">الحالة</th>
                <th class="p-3 text-end">التفاصيل</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($orders as $order)
                <tr>
                  <td class="p-3 fw-bold">#{{ $order->id }}</td>
                  <td class="p-3">{{ $order->created_at->format('Y-m-d') }}</td>
                  <td class="p-3">{{ $order->sub_orders_count }}</td>
                  <td class="p-3 fw-semibold">{{ number_format((float) $order->total_price, 2) }} جنيه</td>
                  <td class="p-3">@include('orders._status', ['status' => $order->status])</td>
                  <td class="p-3 text-end">
                    <a class="btn btn-outline-primary" href="{{ route('orders.show', $order) }}">عرض</a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-4">{{ $orders->links() }}</div>
    @endif
  </div>
@endsection
