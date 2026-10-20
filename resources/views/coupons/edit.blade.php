@extends('layouts.app')

@section('title', 'تعديل كوبون | printore')

@section('content')
  <div class="container py-5">
    <div class="mb-4">
      <a class="text-brand text-decoration-none fw-semibold" href="{{ route('seller.coupons.index') }}">العودة
        لكوبوناتي</a>
      <h1 class="fw-bold mt-2 mb-1">تعديل {{ $coupon->code }}</h1>
      <p class="text-muted-custom mb-0">حدّث بيانات الكوبون أو أوقف استخدامه.</p>
    </div>

    <div class="dashboard-card card border-0">
      <div class="card-body p-4 p-md-5">
        <form method="POST" action="{{ route('seller.coupons.update', $coupon) }}">
          @csrf
          @method('PUT')
          @include('coupons._form', ['coupon' => $coupon, 'submitLabel' => 'حفظ التعديلات'])
        </form>
      </div>
    </div>
  </div>
@endsection