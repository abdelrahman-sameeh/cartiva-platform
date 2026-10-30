@extends('layouts.app')

@section('title', (isset($product) ? 'تعديل منتج' : 'إضافة منتج') . ' | ' . config('app.name'))

@section('content')
  @php
    $isEditing = isset($product);
    $variantRows = old('variants', $isEditing
      ? $product->variants->map(fn($variant) => [
          'id' => $variant->id,
          'sku' => $variant->sku,
          'price' => $variant->price,
          'stock' => $variant->stock,
          'options' => $variant->options->map(fn($option) => [
              'key' => $option->key,
              'value' => $option->value,
          ])->values()->all(),
        ])->all()
      : [['sku' => '', 'price' => '', 'stock' => 0, 'options' => []]]);
    $selectedSubCategories = array_map('intval', old(
      'sub_categories',
      $isEditing ? $product->sub_categories->pluck('id')->all() : []
    ));
    $attributeRows = old('attributes', $isEditing
      ? $product->attributes->map(fn($attribute) => [
          'key' => $attribute->key,
          'value' => $attribute->value,
          'numeric_value' => $attribute->numeric_value,
          'unit' => $attribute->unit,
        ])->all()
      : []);
    $deletedPictureIds = array_map('intval', old('deleted_picture_ids', []));
    $visiblePictureCount = $isEditing
      ? $product->pictures->whereNotIn('id', $deletedPictureIds)->count()
      : 0;
    $nextAttributeIndex = empty($attributeRows) ? 0 : max(array_keys($attributeRows)) + 1;
    $subCategoriesByCategory = $subCategories->groupBy(fn ($subCategory) => $subCategory->category->title);
  @endphp

  <div class="container product-create-page py-4 py-lg-5">
    <header class="product-create-header">
      <div>
        <a class="product-create-back" href="{{ route('seller.products.index') }}">
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
          العودة لمنتجاتي
        </a>
        <span class="product-create-eyebrow">إدارة المنتجات</span>
        <h1>{{ $isEditing ? 'تعديل ' . $product->title : 'إضافة منتج جديد' }}</h1>
        <p>{{ $isEditing ? 'راجع بيانات المنتج وحدّث ما يحتاج للتغيير.' : 'رتّب بيانات المنتج كما سيشاهدها العميل، ثم أضف النسخ والمخزون المتاح.' }}</p>
      </div>
      <div class="product-create-actions">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('seller.fee-calculator') }}">
          <i class="bi bi-calculator" aria-hidden="true"></i>
          احسب الربح والرسوم
        </a>
        <div class="product-create-status" aria-label="حالة المنتج">
          <span class="status-dot" aria-hidden="true"></span>
          {{ $isEditing ? 'سيتم تطبيق التعديلات بعد الحفظ' : 'سيتم نشر المنتج بعد الحفظ' }}
        </div>
      </div>
    </header>

    @if ($errors->any())
      <div class="alert alert-danger product-form-errors" role="alert">
        <div class="d-flex gap-3">
          <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
          <div>
            <strong>راجع البيانات التالية قبل الحفظ</strong>
            <ul class="mb-0 mt-2">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>
    @endif

    <form id="product-form" method="POST"
      action="{{ $isEditing ? route('seller.products.update', $product) : route('seller.products.store') }}"
      enctype="multipart/form-data">
      @csrf
      @if ($isEditing)
        @method('PUT')
      @endif

      <div class="product-create-layout">
        <div class="product-create-main">
          <section class="product-form-section" aria-labelledby="basic-information-title">
            <div class="product-form-section-heading">
              <span class="product-form-step">1</span>
              <div>
                <h2 id="basic-information-title">المعلومات الأساسية</h2>
                <p>اسم واضح ووصف يساعد العميل يفهم المنتج بسرعة.</p>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="title">اسم المنتج <span class="required-mark">*</span></label>
                <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" type="text"
                  value="{{ old('title', $isEditing ? $product->title : '') }}" maxlength="50"
                  placeholder="مثال: خلاط كهربائي 800 وات" required autofocus>
                <div class="form-text">استخدم اسمًا مختصرًا يوضح نوع المنتج وأهم ميزة فيه.</div>
                @error('title')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <div class="d-flex align-items-center justify-content-between gap-3">
                  <label class="form-label" for="description">وصف المنتج <span class="required-mark">*</span></label>
                  <span class="field-counter"><span id="description-count">{{ mb_strlen(old('description', $isEditing ? $product->description : '')) }}</span>/1000</span>
                </div>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                  rows="6" maxlength="1000" placeholder="اكتب أهم المميزات، الاستخدام، ومحتويات العبوة..." required>{{ old('description', $isEditing ? $product->description : '') }}</textarea>
                @error('description')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </section>

          <section class="product-form-section" aria-labelledby="media-title">
            <div class="product-form-section-heading">
              <span class="product-form-step">2</span>
              <div>
                <h2 id="media-title">صور المنتج</h2>
                <p>استخدم صورًا واضحة بصيغة JPG أو PNG أو WebP، بحد أقصى 5MB للصورة.</p>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-lg-5">
                <label class="product-upload-field @error('cover_image') has-error @enderror" for="cover_image">
                  <span class="product-upload-icon"><i class="bi bi-image" aria-hidden="true"></i></span>
                  <span>
                    <strong>{{ $isEditing ? 'تغيير الصورة الرئيسية' : 'الصورة الرئيسية' }}
                      @unless ($isEditing)<span class="required-mark">*</span>@endunless
                    </strong>
                    <small id="cover-image-name">{{ $isEditing ? 'اتركها بدون اختيار للاحتفاظ بالصورة الحالية' : 'اضغط لاختيار الصورة التي ستظهر في نتائج البحث' }}</small>
                  </span>
                  <span class="btn btn-light btn-sm">اختيار صورة</span>
                  <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp"
                    @required(!$isEditing)>
                </label>
                @error('cover_image')
                  <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-lg-7">
                <div class="product-cover-preview" id="cover-preview-frame">
                  <img id="cover-preview" @if ($isEditing && $product->cover_image) src="{{ $product->cover_image_url }}" @endif
                    alt="معاينة الصورة الرئيسية" @if (!$isEditing || !$product->cover_image) hidden @endif>
                  <div id="cover-preview-placeholder" @if ($isEditing && $product->cover_image) hidden @endif>
                    <i class="bi bi-card-image" aria-hidden="true"></i>
                    <strong>معاينة الصورة الرئيسية</strong>
                    <span>ستظهر الصورة كاملة هنا بعد اختيارها</span>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <label class="product-upload-field product-upload-field-secondary" for="product_pictures">
                  <span class="product-upload-icon"><i class="bi bi-images" aria-hidden="true"></i></span>
                  <span>
                    <strong>{{ $isEditing ? 'إضافة صور أخرى' : 'صور إضافية' }}</strong>
                    <small id="gallery-images-name">اختياري — يمكنك تحديد أكثر من صورة لإضافتها</small>
                  </span>
                  <span class="btn btn-light btn-sm">اختيار الصور</span>
                  <input id="product_pictures" name="product_pictures[]" type="file"
                    accept="image/jpeg,image/png,image/webp" multiple>
                </label>
                @if ($isEditing && $product->pictures->isNotEmpty())
                  <div class="product-gallery-group">
                    <strong class="product-gallery-label">الصور الحالية (<span
                        id="existing-gallery-count">{{ $visiblePictureCount }}</span>)</strong>
                    <div class="product-gallery-preview" id="existing-gallery-preview" aria-label="صور المنتج الحالية">
                      @foreach ($product->pictures as $picture)
                        @php($markedForDeletion = in_array($picture->id, $deletedPictureIds, true))
                        <div class="product-gallery-item @if ($markedForDeletion) is-marked-for-deletion @endif"
                          data-existing-picture>
                          <button class="product-gallery-open" type="button" data-preview-src="{{ $picture->picture_url }}"
                            data-preview-alt="صورة حالية للمنتج {{ $loop->iteration }}"
                            aria-label="معاينة الصورة الحالية {{ $loop->iteration }}">
                            <img src="{{ $picture->picture_url }}" alt="صورة حالية للمنتج {{ $loop->iteration }}">
                          </button>
                          <button class="product-gallery-remove" type="button" data-remove-existing-picture
                            aria-label="{{ $markedForDeletion ? 'التراجع عن حذف' : 'حذف' }} الصورة الحالية {{ $loop->iteration }}">
                            <i class="bi {{ $markedForDeletion ? 'bi-arrow-counterclockwise' : 'bi-trash3' }}" aria-hidden="true"></i>
                          </button>
                          <input name="deleted_picture_ids[]" type="hidden" value="{{ $picture->id }}"
                            @disabled(!$markedForDeletion)>
                        </div>
                      @endforeach
                    </div>
                    <small class="product-gallery-help">الحذف يتم نهائيًا عند حفظ التعديلات، ويمكنك التراجع قبله.</small>
                  </div>
                @endif
                <div class="product-gallery-group" id="new-gallery-group" hidden>
                  <strong class="product-gallery-label">الصور الجديدة</strong>
                  <div class="product-gallery-preview" id="gallery-preview" aria-label="معاينة الصور الجديدة"></div>
                </div>
                <dialog class="product-image-dialog" id="product-image-dialog" aria-labelledby="product-image-dialog-title">
                  <div class="product-image-dialog-header">
                    <strong id="product-image-dialog-title">معاينة الصورة</strong>
                    <button type="button" data-close-image-preview aria-label="إغلاق معاينة الصورة">
                      <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                  </div>
                  <img id="product-image-dialog-picture" src="" alt="">
                </dialog>
              </div>
            </div>
          </section>

          <section class="product-form-section" aria-labelledby="categories-title">
            <div class="product-form-section-heading">
              <span class="product-form-step">3</span>
              <div>
                <h2 id="categories-title">التصنيفات</h2>
                <p>اختر التصنيفات الأقرب للمنتج حتى يسهل على العملاء الوصول إليه.</p>
              </div>
            </div>

            <div class="category-picker @error('sub_categories') has-error @enderror">
              @forelse ($subCategoriesByCategory as $categoryTitle => $categorySubCategories)
                <fieldset class="category-picker-group">
                  <legend>{{ $categoryTitle }}</legend>
                  <div class="category-picker-options">
                    @foreach ($categorySubCategories as $subCategory)
                      <label class="category-choice">
                        <input name="sub_categories[]" type="checkbox" value="{{ $subCategory->id }}"
                          @checked(in_array($subCategory->id, $selectedSubCategories, true))>
                        <span>{{ $subCategory->title }}</span>
                      </label>
                    @endforeach
                  </div>
                </fieldset>
              @empty
                <p class="text-muted-custom mb-0">لا توجد تصنيفات متاحة حاليًا.</p>
              @endforelse
            </div>
            <div class="d-flex justify-content-between gap-3 mt-2">
              <span class="form-text">يمكن اختيار أكثر من تصنيف.</span>
              <span class="form-text"><strong id="selected-categories-count">{{ count($selectedSubCategories) }}</strong> محدد</span>
            </div>
            @error('sub_categories')
              <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
          </section>

          <section class="product-form-section" aria-labelledby="pricing-title">
            <div class="product-form-section-heading">
              <span class="product-form-step">4</span>
              <div>
                <h2 id="pricing-title">التسعير</h2>
                <p>ابدأ بسعر البيع العادي، وأضف خصمًا فقط لو المنتج عليه عرض حاليًا.</p>
              </div>
            </div>

            <div class="pricing-fields">
              <div class="pricing-field-card">
                <span class="pricing-field-number">1</span>
                <div>
                  <label class="form-label" for="price">السعر قبل الخصم <span class="required-mark">*</span></label>
                  <div class="input-group">
                    <input class="form-control @error('price') is-invalid @enderror" id="price" name="price"
                      type="number" min="0" step="0.01" value="{{ old('price', $isEditing ? $product->price : '') }}"
                      placeholder="0.00" required>
                    <span class="input-group-text">جنيه</span>
                  </div>
                  <div class="form-text">السعر الطبيعي الذي سيظهر مشطوبًا وقت العرض.</div>
                  @error('price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="pricing-field-card">
                <span class="pricing-field-number">2</span>
                <div>
                  <label class="form-label" for="discount_amount">قيمة الخصم <span class="optional-label">اختياري</span></label>
                  <div class="input-group">
                    <input class="form-control @error('discount_amount') is-invalid @enderror" id="discount_amount"
                      name="discount_amount" type="number" min="0" step="0.01"
                      value="{{ old('discount_amount', $isEditing ? $product->discount_amount : 0) }}">
                    <span class="input-group-text">جنيه</span>
                  </div>
                  <div class="form-text">اتركها صفرًا إذا لم يكن هناك خصم.</div>
                  @error('discount_amount')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="pricing-result-card" aria-live="polite">
                <span>السعر الذي سيدفعه العميل</span>
                <strong><span id="pricing-final-price">—</span> جنيه</strong>
                <small id="pricing-discount-status">أدخل السعر لحساب السعر النهائي</small>
              </div>
            </div>
          </section>

          <section class="product-form-section" aria-labelledby="variants-title">
            <div class="product-form-section-heading">
              <span class="product-form-step">5</span>
              <div>
                <h2 id="variants-title">النسخ والمخزون</h2>
                <p>كل نسخة تمثل اختيارًا يمكن شراؤه وله مخزونه الخاص.</p>
              </div>
            </div>
            @include('products._variants', ['variantRows' => $variantRows])
          </section>

          <section class="product-form-section" aria-labelledby="attributes-title">
            <div class="product-form-section-heading product-form-section-heading-action">
              <span class="product-form-step">6</span>
              <div>
                <h2 id="attributes-title">مواصفات المنتج</h2>
                <p>معلومات وصفية تساعد العميل في المقارنة، مثل الخامة أو الوزن أو القدرة.</p>
              </div>
              <button class="btn btn-outline-primary btn-sm" id="add-attribute" type="button">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                إضافة مواصفة
              </button>
            </div>

            <div class="product-attributes-list" id="attributes-list" data-next-index="{{ $nextAttributeIndex }}">
              @forelse ($attributeRows as $index => $attribute)
                <div class="product-attribute-row">
                  <div><label class="visually-hidden" for="attribute-{{ $index }}-key">اسم المواصفة</label><input class="form-control @error("attributes.$index.key") is-invalid @enderror" id="attribute-{{ $index }}-key" name="attributes[{{ $index }}][key]" type="text" value="{{ $attribute['key'] ?? '' }}" placeholder="المواصفة، مثل الوزن" required></div>
                  <div><label class="visually-hidden" for="attribute-{{ $index }}-value">قيمة المواصفة</label><input class="form-control @error("attributes.$index.value") is-invalid @enderror" id="attribute-{{ $index }}-value" name="attributes[{{ $index }}][value]" type="text" value="{{ $attribute['value'] ?? '' }}" placeholder="القيمة، مثل 1.5 كجم" required></div>
                  <div><label class="visually-hidden" for="attribute-{{ $index }}-numeric">القيمة الرقمية</label><input class="form-control" id="attribute-{{ $index }}-numeric" name="attributes[{{ $index }}][numeric_value]" type="number" step="any" value="{{ $attribute['numeric_value'] ?? '' }}" placeholder="رقم اختياري"></div>
                  <div><label class="visually-hidden" for="attribute-{{ $index }}-unit">الوحدة</label><input class="form-control" id="attribute-{{ $index }}-unit" name="attributes[{{ $index }}][unit]" type="text" value="{{ $attribute['unit'] ?? '' }}" placeholder="الوحدة"></div>
                  <button class="btn btn-icon-danger remove-attribute" type="button" aria-label="حذف المواصفة"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                </div>
              @empty
                <div class="product-form-empty" id="attributes-empty-state">
                  <i class="bi bi-list-check" aria-hidden="true"></i>
                  <p><strong>لا توجد مواصفات إضافية</strong><span>أضف فقط المعلومات المفيدة لاتخاذ قرار الشراء.</span></p>
                </div>
              @endforelse
            </div>
          </section>
        </div>

        <aside class="product-create-sidebar">
          <div class="product-publish-card">
            <div class="product-publish-heading">
              <div><span>ملخص المنتج</span><strong id="product-summary-title">{{ old('title', $isEditing ? $product->title : '') ?: 'منتج جديد' }}</strong></div>
              <i class="bi bi-bag-check" aria-hidden="true"></i>
            </div>

            <dl class="product-publish-summary">
              <div><dt>السعر بعد الخصم</dt><dd><span id="product-final-price">—</span> جنيه</dd></div>
              <div><dt>التصنيفات</dt><dd><span id="summary-categories-count">{{ count($selectedSubCategories) }}</span> محدد</dd></div>
              <div><dt>نسخ المنتج</dt><dd><span id="summary-variants-count">{{ count($variantRows) }}</span> نسخة</dd></div>
            </dl>

            <div class="product-publish-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>تأكد من الصورة والمخزون والسعر قبل نشر المنتج للعملاء.</span></div>

            <button class="btn btn-brand w-100" id="save-product-button" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i> {{ $isEditing ? 'حفظ التعديلات' : 'حفظ ونشر المنتج' }}</button>
            <a class="btn btn-light w-100" href="{{ route('seller.products.index') }}">إلغاء</a>
          </div>
        </aside>
      </div>
    </form>
  </div>
@endsection

@push('scripts')
  @include('products._variant_script')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('product-form');
      const title = document.getElementById('title');
      const description = document.getElementById('description');
      const price = document.getElementById('price');
      const discount = document.getElementById('discount_amount');
      const coverInput = document.getElementById('cover_image');
      const galleryInput = document.getElementById('product_pictures');
      const galleryPreview = document.getElementById('gallery-preview');
      const newGalleryGroup = document.getElementById('new-gallery-group');
      const imageDialog = document.getElementById('product-image-dialog');
      const imageDialogPicture = document.getElementById('product-image-dialog-picture');
      const categoryInputs = form.querySelectorAll('input[name="sub_categories[]"]');
      const attributesList = document.getElementById('attributes-list');
      const variantsList = document.getElementById('variants-list');
      const initialCoverSrc = document.getElementById('cover-preview').getAttribute('src');
      const coverEmptyMessage = @json($isEditing ? 'اتركها بدون اختيار للاحتفاظ بالصورة الحالية' : 'اضغط لاختيار الصورة التي ستظهر في نتائج البحث');
      let coverPreviewUrl;
      let selectedGalleryFiles = [];
      let galleryPreviewUrls = [];

      const updateSummary = () => {
        const basePrice = Number(price.value);
        const discountValue = Number(discount.value) || 0;
        const selectedCategories = [...categoryInputs].filter(input => input.checked).length;
        const hasPrice = price.value !== '' && Number.isFinite(basePrice);
        const hasInvalidDiscount = hasPrice && discountValue > basePrice;
        const finalPrice = hasPrice && !hasInvalidDiscount ? (basePrice - discountValue).toFixed(2) : '—';
        const pricingStatus = document.getElementById('pricing-discount-status');

        document.getElementById('product-summary-title').textContent = title.value.trim() || 'منتج جديد';
        document.getElementById('description-count').textContent = description.value.length;
        document.getElementById('selected-categories-count').textContent = selectedCategories;
        document.getElementById('summary-categories-count').textContent = selectedCategories;
        document.getElementById('summary-variants-count').textContent = variantsList.querySelectorAll('.product-variant-row').length;
        document.getElementById('product-final-price').textContent = finalPrice;
        document.getElementById('pricing-final-price').textContent = finalPrice;
        discount.max = hasPrice ? basePrice : '';

        pricingStatus.textContent = hasInvalidDiscount
          ? 'قيمة الخصم لا يمكن أن تكون أكبر من السعر'
          : discountValue > 0 && hasPrice
            ? `العميل سيوفر ${discountValue.toFixed(2)} جنيه`
            : hasPrice ? 'لا يوجد خصم على المنتج' : 'أدخل السعر لحساب السعر النهائي';
        pricingStatus.closest('.pricing-result-card').classList.toggle('has-error', hasInvalidDiscount);
      };

      [title, description, price, discount, ...categoryInputs].forEach(input => {
        input.addEventListener('input', updateSummary);
        input.addEventListener('change', updateSummary);
      });
      new MutationObserver(updateSummary).observe(variantsList, { childList: true });

      coverInput.addEventListener('change', () => {
        const file = coverInput.files[0];
        const preview = document.getElementById('cover-preview');
        const placeholder = document.getElementById('cover-preview-placeholder');
        document.getElementById('cover-image-name').textContent = file?.name || coverEmptyMessage;
        if (!file) {
          if (initialCoverSrc) {
            preview.src = initialCoverSrc;
            preview.hidden = false;
            placeholder.hidden = true;
          } else {
            preview.hidden = true;
            placeholder.hidden = false;
            preview.removeAttribute('src');
          }
          return;
        }
        if (coverPreviewUrl) URL.revokeObjectURL(coverPreviewUrl);
        coverPreviewUrl = URL.createObjectURL(file);
        preview.src = coverPreviewUrl;
        preview.hidden = false;
        placeholder.hidden = true;
      });

      const syncGalleryInput = () => {
        const transfer = new DataTransfer();
        selectedGalleryFiles.forEach(file => transfer.items.add(file));
        galleryInput.files = transfer.files;
      };

      const renderGalleryPreview = () => {
        galleryPreviewUrls.forEach(url => URL.revokeObjectURL(url));
        galleryPreviewUrls = [];
        const count = selectedGalleryFiles.length;
        document.getElementById('gallery-images-name').textContent = count ? `تم اختيار ${count} صورة إضافية` : 'اختياري — يمكنك تحديد أكثر من صورة';
        galleryPreview.replaceChildren();
        newGalleryGroup.hidden = count === 0;
        if (!count) return;

        selectedGalleryFiles.forEach((file, index) => {
          const previewUrl = URL.createObjectURL(file);
          galleryPreviewUrls.push(previewUrl);
          const item = document.createElement('div');
          item.className = 'product-gallery-item';
          const openButton = document.createElement('button');
          openButton.className = 'product-gallery-open';
          openButton.type = 'button';
          openButton.dataset.previewSrc = previewUrl;
          openButton.dataset.previewAlt = `معاينة الصورة الجديدة ${index + 1}`;
          openButton.setAttribute('aria-label', `معاينة الصورة الجديدة ${index + 1}`);
          const image = document.createElement('img');
          image.src = previewUrl;
          image.alt = `معاينة الصورة الإضافية ${index + 1}`;
          const removeButton = document.createElement('button');
          removeButton.className = 'product-gallery-remove';
          removeButton.type = 'button';
          removeButton.dataset.removeNewPicture = index;
          removeButton.setAttribute('aria-label', `حذف الصورة الجديدة ${index + 1}`);
          removeButton.innerHTML = '<i class="bi bi-trash3" aria-hidden="true"></i>';
          openButton.appendChild(image);
          item.append(openButton, removeButton);
          galleryPreview.appendChild(item);
        });
      };

      galleryInput.addEventListener('change', () => {
        selectedGalleryFiles = [...galleryInput.files];
        renderGalleryPreview();
      });

      form.addEventListener('click', event => {
        const previewButton = event.target.closest('[data-preview-src]');
        if (previewButton) {
          imageDialogPicture.src = previewButton.dataset.previewSrc;
          imageDialogPicture.alt = previewButton.dataset.previewAlt;
          imageDialog.showModal();
          return;
        }

        if (event.target.closest('[data-close-image-preview]')) {
          imageDialog.close();
          return;
        }

        const removeNewButton = event.target.closest('[data-remove-new-picture]');
        if (removeNewButton) {
          if (imageDialog.open) imageDialog.close();
          selectedGalleryFiles.splice(Number(removeNewButton.dataset.removeNewPicture), 1);
          syncGalleryInput();
          renderGalleryPreview();
          return;
        }

        const removeExistingButton = event.target.closest('[data-remove-existing-picture]');
        if (removeExistingButton) {
          const item = removeExistingButton.closest('[data-existing-picture]');
          const deletionInput = item.querySelector('input[name="deleted_picture_ids[]"]');
          const isMarkedForDeletion = !deletionInput.disabled;
          deletionInput.disabled = isMarkedForDeletion;
          item.classList.toggle('is-marked-for-deletion', !isMarkedForDeletion);
          removeExistingButton.innerHTML = isMarkedForDeletion
            ? '<i class="bi bi-trash3" aria-hidden="true"></i>'
            : '<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>';
          removeExistingButton.setAttribute('aria-label', isMarkedForDeletion ? 'حذف الصورة' : 'التراجع عن حذف الصورة');
          const remainingCount = document.querySelectorAll('[data-existing-picture]:not(.is-marked-for-deletion)').length;
          document.getElementById('existing-gallery-count').textContent = remainingCount;
        }
      });

      imageDialog.addEventListener('click', event => {
        if (event.target === imageDialog) imageDialog.close();
      });

      const updateAttributesEmptyState = () => {
        const emptyState = document.getElementById('attributes-empty-state');
        if (attributesList.querySelector('.product-attribute-row')) {
          emptyState?.remove();
        } else if (!emptyState) {
          attributesList.insertAdjacentHTML('beforeend', '<div class="product-form-empty" id="attributes-empty-state"><i class="bi bi-list-check" aria-hidden="true"></i><p><strong>لا توجد مواصفات إضافية</strong><span>أضف فقط المعلومات المفيدة لاتخاذ قرار الشراء.</span></p></div>');
        }
      };

      document.getElementById('add-attribute').addEventListener('click', () => {
        const index = Number(attributesList.dataset.nextIndex);
        attributesList.dataset.nextIndex = index + 1;
        document.getElementById('attributes-empty-state')?.remove();
        attributesList.insertAdjacentHTML('beforeend', `
          <div class="product-attribute-row">
            <div><label class="visually-hidden" for="attribute-${index}-key">اسم المواصفة</label><input class="form-control" id="attribute-${index}-key" name="attributes[${index}][key]" type="text" placeholder="المواصفة، مثل الوزن" required></div>
            <div><label class="visually-hidden" for="attribute-${index}-value">قيمة المواصفة</label><input class="form-control" id="attribute-${index}-value" name="attributes[${index}][value]" type="text" placeholder="القيمة، مثل 1.5 كجم" required></div>
            <div><label class="visually-hidden" for="attribute-${index}-numeric">القيمة الرقمية</label><input class="form-control" id="attribute-${index}-numeric" name="attributes[${index}][numeric_value]" type="number" step="any" placeholder="رقم اختياري"></div>
            <div><label class="visually-hidden" for="attribute-${index}-unit">الوحدة</label><input class="form-control" id="attribute-${index}-unit" name="attributes[${index}][unit]" type="text" placeholder="الوحدة"></div>
            <button class="btn btn-icon-danger remove-attribute" type="button" aria-label="حذف المواصفة"><i class="bi bi-trash3" aria-hidden="true"></i></button>
          </div>
        `);
        document.getElementById(`attribute-${index}-key`).focus();
      });

      attributesList.addEventListener('click', event => {
        const removeButton = event.target.closest('.remove-attribute');
        if (!removeButton) return;
        removeButton.closest('.product-attribute-row')?.remove();
        updateAttributesEmptyState();
      });

      form.addEventListener('submit', () => {
        const button = document.getElementById('save-product-button');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> جارٍ الحفظ...';
      });

      updateSummary();
    });
  </script>
@endpush
