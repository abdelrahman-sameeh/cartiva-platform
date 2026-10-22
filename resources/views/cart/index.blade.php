@extends('layouts.app')

@section('title', 'سلة التسوق | ' . config('app.name'))

@section('content')
  <div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
      <div>
        <a class="text-brand text-decoration-none fw-semibold" href="{{ route('home') }}#products">متابعة التسوق</a>
        <h1 class="fw-bold mt-2 mb-1">سلة التسوق</h1>
        <p class="text-muted-custom mb-0">{{ $cart['items_count'] }} منتج في السلة.</p>
      </div>

      @if ($cart['items_count'] > 0)
        <form method="POST" action="{{ route('cart.destroy') }}" onsubmit="return confirm('هل أنت متأكد من إفراغ السلة؟')">
          @csrf
          @method('DELETE')
          <button class="btn btn-outline-danger" type="submit">إفراغ السلة</button>
        </form>
      @endif
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

    @if ($cart['items_count'] === 0)
      <div class="dashboard-card card border-0">
        <div class="card-body text-center p-5">
          <div class="fs-1 mb-3">🛒</div>
          <h2 class="h4 fw-bold">سلتك فارغة</h2>
          <p class="text-muted-custom mb-4">اكتشف المنتجات وأضف ما يناسبك إلى السلة.</p>
          <a class="btn btn-brand px-4" href="{{ route('products.search') }}">تصفح المنتجات</a>
        </div>
      </div>
    @else
      <div class="row g-4 align-items-start">
        <div class="col-lg-8">
          @foreach ($cart['groups'] as $group)
            <section class="dashboard-card card border-0 mb-4">
              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                  <h2 class="h5 fw-bold mb-0">منتجات البائع</h2>
                  @if ($group['coupon'])
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge text-bg-success">{{ $group['coupon']['code'] }}</span>
                      <form method="POST" action="{{ route('cart.coupons.destroy', $group['coupon']['id']) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">إزالة الكوبون</button>
                      </form>
                    </div>
                  @endif
                </div>

                <div class="d-grid gap-3">
                  @foreach ($group['items'] as $item)
                    @php
                      $selectableVariants = collect($item['product']['variants'])
                        ->filter(fn($variant) => $variant['stock'] > 0 || $variant['id'] === $item['variant']['id'])
                        ->values();
                    @endphp
                    <article class="row g-3 align-items-center border-bottom pb-3">
                      <div class="col-4 col-md-2">
                        <a href="{{ route('stores.products.show', [$group['seller_id'], $item['product']['id']]) }}">
                          @if ($item['product']['cover_image'])
                            <img class="rounded-3 w-100" style="aspect-ratio: 1; object-fit: cover;"
                              src="{{ $item['product']['cover_image'] }}" alt="{{ $item['product']['title'] }}">
                          @else
                            <span class="rounded-3 bg-light d-flex align-items-center justify-content-center text-muted"
                              style="aspect-ratio: 1;">لا توجد صورة</span>
                          @endif
                        </a>
                      </div>
                      <div class="col-8 col-md-3">
                        <h3 class="h6 fw-bold mb-1">
                          <a class="text-dark text-decoration-none"
                            href="{{ route('stores.products.show', [$group['seller_id'], $item['product']['id']]) }}">
                            {{ $item['product']['title'] }}
                          </a>
                        </h3>
                        <span class="text-brand fw-semibold">{{ number_format($item['unit_price'], 2) }} جنيه</span>
                        <small class="text-muted-custom d-block">{{ $item['variant']['label'] }}</small>
                      </div>
                      <div class="col-12 col-md-5">
                        <form class="cart-variant-form row g-2 align-items-end p-3" method="POST"
                          action="{{ route('cart.items.update', $item['id']) }}"
                          data-variants="{{ $selectableVariants->toJson() }}">
                          @csrf
                          @method('PUT')
                          <input class="selected-variant-id" name="product_variant_id" type="hidden"
                            value="{{ $item['variant']['id'] }}">
                          <div class="col-12">
                            <label class="form-label small mb-1">اختيار المنتج</label>
                            <select class="form-select cart-variant-selector" required>
                              @foreach ($selectableVariants as $variant)
                                <option value="{{ $variant['id'] }}" @selected($variant['id'] === $item['variant']['id'])>
                                  {{ $variant['label'] }} — {{ number_format($variant['price'], 2) }} جنيه
                                  ({{ $variant['stock'] }} متاح)
                                </option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-5">
                            <label class="form-label small mb-1">الكمية</label>
                            <div class="cart-quantity-picker">
                              <button class="btn btn-light cart-quantity-action" type="button" data-action="decrease"
                                aria-label="تقليل الكمية">−</button>
                              <input class="form-control text-center cart-variant-quantity" name="quantity" type="number"
                                min="1" max="{{ max($item['variant']['stock'], 1) }}" value="{{ $item['quantity'] }}"
                                inputmode="numeric" required>
                              <button class="btn btn-light cart-quantity-action" type="button" data-action="increase"
                                aria-label="زيادة الكمية">+</button>
                            </div>
                          </div>
                          <div class="col-7 d-grid">
                            <button class="btn btn-outline-primary cart-update-button" type="submit">حفظ التغيير</button>
                          </div>
                          <div class="col-12 d-flex flex-wrap justify-content-between gap-2 small">
                            <span class="cart-variant-stock text-muted-custom" role="status"></span>
                            <span class="cart-change-status text-muted-custom" aria-live="polite">لا توجد تغييرات</span>
                          </div>
                        </form>
                      </div>
                      <div class="col-12 col-md-2 text-end">
                        <form method="POST" action="{{ route('cart.items.destroy', $item['id']) }}">
                          @csrf
                          @method('DELETE')
                          <button class="btn btn-outline-danger w-100" type="submit">حذف</button>
                        </form>
                      </div>
                    </article>
                  @endforeach
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-4 pt-3">
                  <span>الإجمالي: <strong>{{ number_format($group['summary']['sub_total'], 2) }} جنيه</strong></span>
                  <span>الخصم: <strong class="text-success">{{ number_format($group['summary']['discount'], 2) }}
                      جنيه</strong></span>
                  <span>المطلوب: <strong class="text-brand">{{ number_format($group['summary']['total'], 2) }}
                      جنيه</strong></span>
                </div>
              </div>
            </section>
          @endforeach
        </div>

        <aside class="col-lg-4">
          <div class="dashboard-card card border-0 mb-4">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">إضافة كوبون</h2>
              <form class="d-grid gap-2" method="POST" action="{{ route('cart.coupons.store') }}">
                @csrf
                <label class="visually-hidden" for="coupon-code">كود الكوبون</label>
                <input class="form-control" id="coupon-code" name="code" type="text" value="{{ old('code') }}"
                  placeholder="أدخل كود الكوبون" dir="auto" required>
                <button class="btn btn-outline-primary" type="submit">تطبيق الكوبون</button>
              </form>
            </div>
          </div>

          <div class="dashboard-card card border-0">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-4">ملخص السلة</h2>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-muted-custom">الإجمالي</span>
                <strong>{{ number_format($cart['summary_cart']['subtotal'], 2) }} جنيه</strong>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-muted-custom">الخصم</span>
                <strong class="text-success">{{ number_format($cart['summary_cart']['discount'], 2) }} جنيه</strong>
              </div>
              <hr>
              <div class="d-flex justify-content-between fs-5">
                <span class="fw-bold">المطلوب</span>
                <strong class="text-brand">{{ number_format($cart['summary_cart']['total'], 2) }} جنيه</strong>
              </div>
              <a class="btn btn-brand w-100 mt-4" href="{{ route('orders.create') }}">إتمام الطلب</a>
            </div>
          </div>
        </aside>
      </div>
    @endif
  </div>
@endsection

@push('styles')
  <style>
    .cart-variant-form {
      border: 1px solid var(--border);
      border-radius: 1rem;
      background: var(--surface-soft);
      transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
    }

    .cart-variant-form.is-dirty {
      border-color: rgba(217, 119, 6, .45);
      background: var(--brand-soft);
      box-shadow: 0 0 0 .2rem rgba(217, 119, 6, .07);
    }

    .cart-quantity-picker {
      display: grid;
      grid-template-columns: 2.6rem minmax(3.5rem, 1fr) 2.6rem;
      gap: .35rem;
    }

    .cart-quantity-picker .btn,
    .cart-quantity-picker .form-control {
      min-height: 3.15rem;
      padding-inline: .25rem;
    }

    .cart-quantity-picker .cart-quantity-action {
      font-size: 1.1rem;
    }
  </style>
@endpush

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.cart-variant-form').forEach(form => {
        const variants = JSON.parse(form.dataset.variants);
        const variantInput = form.querySelector('.selected-variant-id');
        const variantSelect = form.querySelector('.cart-variant-selector');
        const quantityInput = form.querySelector('.cart-variant-quantity');
        const updateButton = form.querySelector('.cart-update-button');
        const stockStatus = form.querySelector('.cart-variant-stock');
        const changeStatus = form.querySelector('.cart-change-status');
        const initialVariantId = Number(variantInput.value);
        const initialQuantity = Number(quantityInput.value);

        const clampQuantity = value => Math.min(
          Math.max(Number(value) || 1, 1),
          Number(quantityInput.max)
        );

        const syncFormState = variant => {
          const quantity = Number(quantityInput.value);
          const hasChanges = Number(variantInput.value) !== initialVariantId ||
            quantity !== initialQuantity;
          const isAvailable = variant.stock > 0;
          const hasValidQuantity = quantity >= 1 && quantity <= variant.stock;

          form.classList.toggle('is-dirty', hasChanges);
          stockStatus.textContent = isAvailable ? `متاح ${variant.stock} قطعة` : 'الاختيار الحالي نفد من المخزون';
          stockStatus.classList.toggle('text-danger', !isAvailable);
          changeStatus.textContent = hasChanges ? 'عندك تغييرات غير محفوظة' : 'لا توجد تغييرات';
          changeStatus.classList.toggle('text-brand', hasChanges);
          updateButton.disabled = !hasChanges || !isAvailable || !hasValidQuantity;
        };

        const syncVariant = () => {
          const variant = variants.find(item => Number(item.id) === Number(variantSelect.value));

          if (!variant) {
            return;
          }

          variantInput.value = variant.id;
          quantityInput.max = Math.max(variant.stock, 1);
          quantityInput.value = clampQuantity(quantityInput.value);
          syncFormState(variant);
        };

        variantSelect.addEventListener('change', syncVariant);
        quantityInput.addEventListener('input', () => {
          const variant = variants.find(item => Number(item.id) === Number(variantInput.value));

          if (variant) {
            syncFormState(variant);
          }
        });
        quantityInput.addEventListener('change', () => {
          quantityInput.value = clampQuantity(quantityInput.value);
          syncVariant();
        });
        form.querySelectorAll('.cart-quantity-action').forEach(button => {
          button.addEventListener('click', () => {
            const direction = button.dataset.action === 'increase' ? 1 : -1;
            quantityInput.value = clampQuantity(Number(quantityInput.value) + direction);
            syncVariant();
          });
        });
        form.addEventListener('submit', () => {
          updateButton.disabled = true;
          updateButton.textContent = 'جارٍ الحفظ...';
        });
        variantSelect.value = variantInput.value;
        syncVariant();
      });
    });
  </script>
@endpush
