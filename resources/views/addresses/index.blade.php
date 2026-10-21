@extends('layouts.app')

@section('title', 'عناويني | ' . config('app.name'))

@section('content')
  <div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
      <div>
        <a class="text-brand text-decoration-none fw-semibold" href="{{ route('dashboard') }}">العودة للحساب</a>
        <h1 class="fw-bold mt-2 mb-1">عناويني</h1>
        <p class="text-muted-custom mb-0">أضف عناوين الشحن وحدد العنوان الافتراضي.</p>
      </div>
      <a class="btn btn-brand px-4" href="{{ route('addresses.create') }}">إضافة عنوان</a>
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

    @if ($addresses->isEmpty())
      <div class="dashboard-card card border-0">
        <div class="card-body text-center p-5">
          <div class="fs-1 mb-3">📍</div>
          <h2 class="h4 fw-bold">لا توجد عناوين بعد</h2>
          <p class="text-muted-custom mb-4">أضف عنوانك الأول ليكون جاهزًا عند إتمام الطلب.</p>
          <a class="btn btn-brand px-4" href="{{ route('addresses.create') }}">إضافة أول عنوان</a>
        </div>
      </div>
    @else
      <div class="row g-4">
        @foreach ($addresses as $address)
          <div class="col-md-6">
            <article class="dashboard-card card border-0 h-100">
              <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                  <div>
                    <h2 class="h5 fw-bold mb-1">{{ $address->city }}، {{ $countries[$address->country] }}</h2>
                    <p class="text-muted-custom mb-0">{{ $address->street }}</p>
                  </div>
                  @if ($address->is_default)
                    <span class="badge text-bg-success">الافتراضي</span>
                  @endif
                </div>

                @if ($address->note)
                  <p class="mb-2"><strong>ملاحظة:</strong> {{ $address->note }}</p>
                @endif

                @if ($address->latitude !== null && $address->longitude !== null)
                  <small class="text-muted-custom">الموقع: {{ $address->latitude }}، {{ $address->longitude }}</small>
                @endif

                <div class="d-flex gap-2 mt-auto pt-4">
                  <a class="btn btn-outline-primary flex-grow-1" href="{{ route('addresses.edit', $address) }}">تعديل</a>
                  @unless ($address->is_default)
                    <form class="flex-grow-1" method="POST" action="{{ route('addresses.destroy', $address) }}"
                      onsubmit="return confirm('هل أنت متأكد من حذف هذا العنوان؟')">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-outline-danger w-100" type="submit">حذف</button>
                    </form>
                  @endunless
                </div>
              </div>
            </article>
          </div>
        @endforeach
      </div>
    @endif
  </div>
@endsection
