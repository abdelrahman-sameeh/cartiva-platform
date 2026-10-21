@extends('layouts.app')

@section('title', 'إضافة كوبون | ' . config('app.name'))

@section('content')
  <div class="container py-5">
    <div class="mb-4">
      <a class="text-brand text-decoration-none fw-semibold" href="{{ route('seller.coupons.index') }}">العودة
        لكوبوناتي</a>
      <h1 class="fw-bold mt-2 mb-1">إضافة كوبون جديد</h1>
      <p class="text-muted-custom mb-0">حدد كود الخصم ونسبته ومدة صلاحيته.</p>
    </div>

    <div class="dashboard-card card border-0">
      <div class="card-body p-4 p-md-5">
        <form method="POST" action="{{ route('seller.coupons.store') }}">
          @csrf
          @include('coupons._form', ['coupon' => null, 'submitLabel' => 'حفظ الكوبون'])
        </form>
      </div>
    </div>
  </div>
@endsection
