@extends('layouts.app')

@section('title', 'تعديل منتج | ' . config('app.name'))

@section('content')
  @php
    $selectedSubCategories = old('sub_categories', $product->sub_categories->pluck('id')->all());
    $attributeRows = old('attributes', $product->attributes->map(fn($attribute) => [
      'key' => $attribute->key,
      'value' => $attribute->value,
    ])->all());
    $nextAttributeIndex = empty($attributeRows) ? 0 : max(array_keys($attributeRows)) + 1;
    $variantRows = old('variants', $product->variants->map(fn($variant) => [
      'size' => $variant->size,
      'color' => $variant->color,
      'stock' => $variant->stock,
    ])->all());
  @endphp

  <div class="container py-5">
    <div class="mb-4">
      <a class="text-brand text-decoration-none fw-semibold" href="{{ route('seller.products.index') }}">
        العودة لمنتجاتي
      </a>
      <h1 class="fw-bold mt-2 mb-1">تعديل {{ $product->title }}</h1>
      <p class="text-muted-custom mb-0">حدّث بيانات المنتج أو أضف صورًا وخصائص جديدة.</p>
    </div>

    <div class="dashboard-card card border-0">
      <div class="card-body p-4 p-md-5">
        @if ($errors->any())
          <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('seller.products.update', $product) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold" for="title">اسم المنتج</label>
              <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" type="text"
                value="{{ old('title', $product->title) }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold" for="price">السعر</label>
              <input class="form-control @error('price') is-invalid @enderror" id="price" name="price" type="number"
                min="0" step="0.01" value="{{ old('price', $product->price) }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold" for="discount_amount">خصم المنتج (جنيه)</label>
              <input class="form-control @error('discount_amount') is-invalid @enderror" id="discount_amount"
                name="discount_amount" type="number" min="0" step="0.01"
                value="{{ old('discount_amount', $product->discount_amount) }}">
              @error('discount_amount')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold" for="description">وصف المنتج</label>
              <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                name="description" rows="5" required>{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold" for="cover_image">تغيير الصورة الرئيسية</label>
              <input class="form-control @error('cover_image') is-invalid @enderror" id="cover_image" name="cover_image"
                type="file" accept="image/jpeg,image/png,image/webp">
              <div class="form-text">اتركها فارغة للاحتفاظ بالصورة الحالية.</div>
            </div>

            @if ($product->cover_image)
              <div class="col-12">
                <img class="rounded border" style="width: 120px; height: 120px; object-fit: cover;"
                  src="{{ $product->cover_image_url }}" alt="الصورة الحالية لـ {{ $product->title }}">
              </div>
            @endif

            <div class="col-12">
              <label class="form-label fw-semibold" for="product_pictures">إضافة صور أخرى</label>
              <input class="form-control @error('product_pictures') is-invalid @enderror" id="product_pictures"
                name="product_pictures[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold" for="sub_categories">التصنيفات الفرعية</label>
              <select class="form-select @error('sub_categories') is-invalid @enderror" id="sub_categories"
                name="sub_categories[]" multiple required size="5">
                @foreach ($subCategories as $subCategory)
                  <option value="{{ $subCategory->id }}" @selected(in_array($subCategory->id, $selectedSubCategories))>
                    {{ $subCategory->category->title }} / {{ $subCategory->title }}
                  </option>
                @endforeach
              </select>
            </div>

            @include('products._variants', ['variantRows' => $variantRows])

            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                <label class="form-label fw-semibold mb-0">خصائص المنتج</label>
                <button class="btn btn-outline-primary" id="add-attribute" type="button">إضافة خاصية</button>
              </div>

              <div class="d-grid gap-2" id="attributes-list" data-next-index="{{ $nextAttributeIndex }}">
                @forelse ($attributeRows as $index => $attribute)
                  <div class="row g-2 align-items-start product-attribute-row">
                    <div class="col-md-5">
                      <input class="form-control @error("attributes.$index.key") is-invalid @enderror"
                        name="attributes[{{ $index }}][key]" type="text" value="{{ $attribute['key'] ?? '' }}"
                        placeholder="الخاصية، مثل الخامة" required>
                      @error("attributes.$index.key")
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="col-md-5">
                      <input class="form-control @error("attributes.$index.value") is-invalid @enderror"
                        name="attributes[{{ $index }}][value]" type="text" value="{{ $attribute['value'] ?? '' }}"
                        placeholder="القيمة، مثل قطن" required>
                      @error("attributes.$index.value")
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="col-md-2 d-grid">
                      <button class="btn btn-outline-danger remove-attribute" type="button">حذف</button>
                    </div>
                  </div>
                @empty
                  <p class="text-muted-custom mb-0" id="attributes-empty-state">لا توجد خصائص لهذا المنتج.</p>
                @endforelse
              </div>
            </div>
          </div>

          <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
            <button class="btn btn-brand px-4" type="submit">حفظ التعديلات</button>
            <a class="btn btn-light px-4" href="{{ route('seller.products.index') }}">إلغاء</a>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  @include('products._variant_script')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const attributesList = document.getElementById('attributes-list');
      const addAttributeButton = document.getElementById('add-attribute');

      const updateEmptyState = () => {
        const emptyState = document.getElementById('attributes-empty-state');

        if (attributesList.querySelector('.product-attribute-row')) {
          emptyState?.remove();
        } else if (!emptyState) {
          attributesList.insertAdjacentHTML(
            'beforeend',
            '<p class="text-muted-custom mb-0" id="attributes-empty-state">لا توجد خصائص لهذا المنتج.</p>'
          );
        }
      };

      addAttributeButton.addEventListener('click', () => {
        const index = Number(attributesList.dataset.nextIndex);
        attributesList.dataset.nextIndex = index + 1;
        document.getElementById('attributes-empty-state')?.remove();

        attributesList.insertAdjacentHTML('beforeend', `
              <div class="row g-2 align-items-start product-attribute-row">
                <div class="col-md-5">
                  <input class="form-control" name="attributes[${index}][key]" type="text"
                    placeholder="الخاصية، مثل الخامة" required>
                </div>
                <div class="col-md-5">
                  <input class="form-control" name="attributes[${index}][value]" type="text"
                    placeholder="القيمة، مثل قطن" required>
                </div>
                <div class="col-md-2 d-grid">
                  <button class="btn btn-outline-danger remove-attribute" type="button">حذف</button>
                </div>
              </div>
            `);

        attributesList.querySelector('.product-attribute-row:last-child input')?.focus();
      });

      attributesList.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-attribute');

        if (removeButton) {
          removeButton.closest('.product-attribute-row')?.remove();
          updateEmptyState();
        }
      });
    });
  </script>
@endpush
