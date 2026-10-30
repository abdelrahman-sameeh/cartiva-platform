@php
  $nextVariantIndex = empty($variantRows) ? 0 : max(array_keys($variantRows)) + 1;
@endphp

<div class="variant-builder col-12">
  <div class="variant-builder-guide">
    <i class="bi bi-lightbulb" aria-hidden="true"></i>
    <p>
      <strong>منتج بسيط؟</strong>
      استخدم نسخة واحدة واترك الاختيارات فارغة. لو المنتج متوفر بأكثر من لون أو مقاس أو سعة، أضف نسخة لكل تركيبة.
    </p>
  </div>

  @error('variants')
    <div class="text-danger small mb-3">{{ $message }}</div>
  @enderror

  <div class="variants-list" id="variants-list" data-next-index="{{ $nextVariantIndex }}">
    @foreach ($variantRows as $index => $variant)
      @php
        $options = $variant['options'] ?? [];
        $nextOptionIndex = empty($options) ? 0 : max(array_keys($options)) + 1;
      @endphp
      <article class="product-variant-row" data-variant-index="{{ $index }}">
        @if (!empty($variant['id']))
          <input name="variants[{{ $index }}][id]" type="hidden" value="{{ $variant['id'] }}">
        @endif

        <header class="variant-row-heading">
          <div>
            <span class="variant-number">{{ $loop->iteration }}</span>
            <div>
              <strong class="variant-title">نسخة {{ $loop->iteration }}</strong>
              <small>حدد المخزون وأي اختلاف في السعر أو الاختيارات</small>
            </div>
          </div>
          <button class="btn btn-icon-danger remove-variant" type="button" aria-label="حذف النسخة"
            @disabled(count($variantRows) === 1)>
            <i class="bi bi-trash3" aria-hidden="true"></i>
          </button>
        </header>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="variant-{{ $index }}-stock">المخزون <span class="required-mark">*</span></label>
            <input class="form-control @error("variants.$index.stock") is-invalid @enderror" id="variant-{{ $index }}-stock"
              name="variants[{{ $index }}][stock]" type="number" min="0" value="{{ $variant['stock'] ?? 0 }}" required>
            <div class="form-text">عدد القطع المتاحة من هذه النسخة.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="variant-{{ $index }}-sku">SKU <span class="optional-label">اختياري</span></label>
            <input class="form-control @error("variants.$index.sku") is-invalid @enderror" id="variant-{{ $index }}-sku"
              name="variants[{{ $index }}][sku]" type="text" value="{{ $variant['sku'] ?? '' }}"
              placeholder="مثال: MIXER-BLK">
            <div class="form-text">كود داخلي لتمييز النسخة.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="variant-{{ $index }}-price">سعر النسخة <span class="optional-label">اختياري</span></label>
            <div class="input-group">
              <input class="form-control @error("variants.$index.price") is-invalid @enderror"
                id="variant-{{ $index }}-price" name="variants[{{ $index }}][price]" type="number" min="0"
                step="0.01" value="{{ $variant['price'] ?? '' }}" placeholder="السعر الأساسي">
              <span class="input-group-text">جنيه</span>
            </div>
          </div>
        </div>

        <div class="variant-options-panel">
          <div class="variant-options-heading">
            <div>
              <strong>اختيارات النسخة</strong>
              <small>مثال: اللون = أسود، أو السعة = 256GB</small>
            </div>
            <button class="btn btn-outline-primary btn-sm add-variant-option" type="button">
              <i class="bi bi-plus-lg" aria-hidden="true"></i>
              إضافة اختيار
            </button>
          </div>

          <div class="variant-options-list" data-next-option-index="{{ $nextOptionIndex }}">
            @forelse ($options as $optionIndex => $option)
              <div class="variant-option-row">
                <div>
                  <label class="visually-hidden" for="variant-{{ $index }}-option-{{ $optionIndex }}-key">اسم الاختيار</label>
                  <input class="form-control @error("variants.$index.options.$optionIndex.key") is-invalid @enderror"
                    id="variant-{{ $index }}-option-{{ $optionIndex }}-key"
                    name="variants[{{ $index }}][options][{{ $optionIndex }}][key]" type="text"
                    value="{{ $option['key'] ?? '' }}" placeholder="الاختيار، مثل اللون" required>
                </div>
                <div>
                  <label class="visually-hidden" for="variant-{{ $index }}-option-{{ $optionIndex }}-value">قيمة الاختيار</label>
                  <input class="form-control @error("variants.$index.options.$optionIndex.value") is-invalid @enderror"
                    id="variant-{{ $index }}-option-{{ $optionIndex }}-value"
                    name="variants[{{ $index }}][options][{{ $optionIndex }}][value]" type="text"
                    value="{{ $option['value'] ?? '' }}" placeholder="القيمة، مثل أسود" required>
                </div>
                <button class="btn btn-icon-danger remove-variant-option" type="button" aria-label="حذف الاختيار">
                  <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
              </div>
            @empty
              <div class="variant-options-empty">لا توجد اختيارات — هذه هي النسخة الأساسية للمنتج.</div>
            @endforelse
          </div>
        </div>
      </article>
    @endforeach
  </div>

  <button class="add-variant-button" id="add-variant" type="button">
    <i class="bi bi-plus-circle" aria-hidden="true"></i>
    <span><strong>إضافة نسخة أخرى</strong><small>أضف مخزونًا مستقلًا لتركيبة مختلفة</small></span>
  </button>
</div>
