@extends('layouts.app')

@section('title', $product->title . ' | ' . config('app.name'))

@section('content')
  <div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3 mb-4">
      <div>
        <a class="text-brand text-decoration-none fw-semibold" href="{{ route('seller.products.index') }}">
          العودة لمنتجاتي
        </a>
        <h1 class="fw-bold mt-2 mb-1">{{ $product->title }}</h1>
        <p class="text-muted-custom mb-0">تفاصيل المنتج كما تم تسجيلها في المتجر.</p>
      </div>

      <div class="d-flex gap-2">
        <a class="btn btn-outline-primary px-4" href="{{ route('seller.products.edit', $product) }}">تعديل المنتج</a>
        <form method="POST" action="{{ route('seller.products.destroy', $product) }}"
          onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
          @csrf
          @method('DELETE')
          <button class="btn btn-outline-danger px-4" type="submit">حذف</button>
        </form>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="product-main-image-frame dashboard-card card border-0 overflow-hidden">
          @if ($product->cover_image)
            <img class="product-detail-image" src="{{ $product->cover_image_url }}" alt="{{ $product->title }}">
          @else
            <div class="product-detail-placeholder d-flex align-items-center justify-content-center bg-light text-muted">
              لا توجد صورة رئيسية
            </div>
          @endif
        </div>

        @if ($product->pictures->isNotEmpty())
          <div class="mt-3">
            <h2 class="h6 fw-bold mb-3">صور إضافية</h2>
            <div class="product-gallery">
              @foreach ($product->pictures as $picture)
                <div class="product-gallery-item">
                  <a class="product-thumbnail-frame d-block dashboard-card card border-0 overflow-hidden"
                    href="{{ $picture->picture_url }}" target="_blank" rel="noopener">
                    <img class="product-detail-image" src="{{ $picture->picture_url }}"
                      alt="صورة إضافية لـ {{ $product->title }}">
                  </a>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </div>

      <div class="col-lg-5">
        <div class="dashboard-card card border-0 mb-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">نسخ المنتج</h2>
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th>الاختيارات</th>
                    <th>SKU</th>
                    <th>السعر</th>
                    <th class="text-end">المخزون</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($product->variants as $variant)
                    <tr>
                      <td>{{ $variant->option_label }}</td>
                      <td>{{ $variant->sku ?: '—' }}</td>
                      <td>{{ number_format($variant->price ?? $product->price, 2) }} جنيه</td>
                      <td class="text-end">{{ $variant->stock }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="dashboard-card card border-0 mb-4">
          <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
              <div>
                <span class="text-muted-custom d-block mb-1">السعر</span>
                @if ($product->discount_amount > 0)
                  <span class="text-muted-custom text-decoration-line-through d-block">
                    {{ number_format($product->price, 2) }} جنيه
                  </span>
                @endif
                <strong class="text-brand fs-3">{{ number_format($product->price_after_discount, 2) }} جنيه</strong>
              </div>

              @if ($product->discount_amount > 0)
                <span class="badge text-bg-danger p-2">خصم {{ number_format($product->discount_amount, 2) }} جنيه</span>
              @endif
            </div>

            <div class="d-flex justify-content-between py-3 border-top">
              <span class="text-muted-custom">المخزون</span>
              <strong>{{ $product->stock }} قطعة</strong>
            </div>
            <div class="d-flex justify-content-between py-3 border-top">
              <span class="text-muted-custom">الحالة</span>
              @if ($product->is_active)
                <span class="badge text-bg-success">نشط</span>
              @else
                <span class="badge text-bg-secondary">غير نشط</span>
              @endif
            </div>
            <div class="d-flex justify-content-between py-3 border-top">
              <span class="text-muted-custom">تاريخ الإضافة</span>
              <span>{{ $product->created_at?->format('Y-m-d') }}</span>
            </div>
          </div>
        </div>

        <div class="dashboard-card card border-0 mb-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">وصف المنتج</h2>
            <p class="mb-0" style="white-space: pre-line;">{{ $product->description }}</p>
          </div>
        </div>

        <div class="dashboard-card card border-0 mb-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">التصنيفات</h2>
            @forelse ($product->sub_categories as $subCategory)
              <span class="badge text-bg-light border text-dark me-1 mb-1">
                {{ $subCategory->category->title }} / {{ $subCategory->title }}
              </span>
            @empty
              <p class="text-muted-custom mb-0">لا توجد تصنيفات مرتبطة.</p>
            @endforelse
          </div>
        </div>

        <div class="dashboard-card card border-0">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">خصائص المنتج</h2>
            @if ($product->attributes->isEmpty())
              <p class="text-muted-custom mb-0">لا توجد خصائص مسجلة.</p>
            @else
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <tbody>
                    @foreach ($product->attributes as $attribute)
                      <tr>
                        <th class="ps-0">{{ $attribute->key }}</th>
                        <td class="text-end pe-0">
                          {{ $attribute->value }}
                          @if ($attribute->numeric_value !== null)
                            <span class="text-muted-custom">({{ $attribute->numeric_value }} {{ $attribute->unit }})</span>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
