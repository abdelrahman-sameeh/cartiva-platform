@extends('layouts.app')

@section('title', 'تعديل عنوان | ' . config('app.name'))

@section('content')
  <div class="container py-5">
    <div class="mb-4">
      <a class="text-brand text-decoration-none fw-semibold" href="{{ route('addresses.index') }}">العودة لعناويني</a>
      <h1 class="fw-bold mt-2 mb-1">تعديل العنوان</h1>
      <p class="text-muted-custom mb-0">حدّث بيانات العنوان أو اجعله العنوان الافتراضي.</p>
    </div>

    <div class="dashboard-card card border-0">
      <div class="card-body p-4 p-md-5">
        <form method="POST" action="{{ route('addresses.update', $address) }}">
          @csrf
          @method('PUT')
          @include('addresses._form', ['address' => $address, 'submitLabel' => 'حفظ التعديلات'])
        </form>
      </div>
    </div>
  </div>
@endsection
