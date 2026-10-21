@php
  $nextVariantIndex = empty($variantRows) ? 0 : max(array_keys($variantRows)) + 1;
@endphp

<div class="col-12">
  <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
    <div>
      <label class="form-label fw-semibold mb-0">نسخ المنتج والمخزون</label>
      <div class="form-text">للمنتج البسيط اترك الاختيارات فارغة. أضف خيارات مثل المقاس أو اللون أو السعة عند الحاجة.</div>
    </div>
    <button class="btn btn-outline-primary" id="add-variant" type="button">إضافة نسخة</button>
  </div>

  <div class="d-grid gap-3" id="variants-list" data-next-index="{{ $nextVariantIndex }}">
    @foreach ($variantRows as $index => $variant)
      @php
        $options = $variant['options'] ?? [];
        $nextOptionIndex = empty($options) ? 0 : max(array_keys($options)) + 1;
      @endphp
      <div class="product-variant-row border rounded-4 p-3" data-variant-index="{{ $index }}">
        @if (!empty($variant['id']))
          <input name="variants[{{ $index }}][id]" type="hidden" value="{{ $variant['id'] }}">
        @endif
        <div class="row g-2 align-items-start">
          <div class="col-md-4">
            <label class="form-label small">SKU اختياري</label>
            <input class="form-control @error("variants.$index.sku") is-invalid @enderror"
              name="variants[{{ $index }}][sku]" type="text" value="{{ $variant['sku'] ?? '' }}"
              placeholder="مثال: PHONE-256-BLK">
          </div>
          <div class="col-md-3">
            <label class="form-label small">سعر مختلف اختياري</label>
            <input class="form-control @error("variants.$index.price") is-invalid @enderror"
              name="variants[{{ $index }}][price]" type="number" min="0" step="0.01"
              value="{{ $variant['price'] ?? '' }}" placeholder="سعر المنتج الأساسي">
          </div>
          <div class="col-md-3">
            <label class="form-label small">المخزون</label>
            <input class="form-control @error("variants.$index.stock") is-invalid @enderror"
              name="variants[{{ $index }}][stock]" type="number" min="0" value="{{ $variant['stock'] ?? 0 }}" required>
          </div>
          <div class="col-md-2 d-grid align-self-end">
            <button class="btn btn-outline-danger remove-variant" type="button">حذف النسخة</button>
          </div>
        </div>
        <div class="mt-3">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <span class="small fw-semibold">اختيارات هذه النسخة</span>
            <button class="btn btn-outline-primary btn-sm add-variant-option" type="button">إضافة اختيار</button>
          </div>
          <div class="variant-options-list d-grid gap-2" data-next-option-index="{{ $nextOptionIndex }}">
            @foreach ($options as $optionIndex => $option)
              <div class="row g-2 variant-option-row">
                <div class="col-md-5"><input class="form-control" name="variants[{{ $index }}][options][{{ $optionIndex }}][key]" type="text" value="{{ $option['key'] ?? '' }}" placeholder="اسم الاختيار، مثل السعة" required></div>
                <div class="col-md-5"><input class="form-control" name="variants[{{ $index }}][options][{{ $optionIndex }}][value]" type="text" value="{{ $option['value'] ?? '' }}" placeholder="القيمة، مثل 256GB" required></div>
                <div class="col-md-2 d-grid"><button class="btn btn-outline-danger remove-variant-option" type="button">حذف</button></div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
