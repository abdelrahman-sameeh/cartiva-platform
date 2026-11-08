@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label fw-semibold" for="code">كود الكوبون</label>
    <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" type="text"
      value="{{ old('code', $coupon?->code) }}" placeholder="مثال: خصم_الصيف_20" dir="auto" required>
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold" for="percentage">نسبة الخصم</label>
    <div class="input-group">
      <input class="form-control @error('percentage') is-invalid @enderror" id="percentage" name="percentage"
        type="number" min="0.01" max="100" step="0.01"
        value="{{ old('percentage', $coupon?->percentage) }}" required>
      <span class="input-group-text">%</span>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold" for="max_usage">الحد الأقصى للاستخدام</label>
    <input class="form-control @error('max_usage') is-invalid @enderror" id="max_usage" name="max_usage"
      type="number" min="{{ max(1, $coupon?->used_count ?? 0) }}"
      value="{{ old('max_usage', $coupon?->max_usage) }}" required>
    @if ($coupon)
      <div class="form-text">تم استخدام الكوبون {{ $coupon->used_count }} مرة.</div>
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold" for="expire_date">تاريخ الانتهاء</label>
    <input class="form-control @error('expire_date') is-invalid @enderror" id="expire_date" name="expire_date"
      type="date" min="{{ now()->toDateString() }}"
      value="{{ old('expire_date', $coupon?->expire_date?->format('Y-m-d')) }}" required>
  </div>

  <div class="col-12">
    <input name="is_active" type="hidden" value="0">
    <div class="form-check form-switch">
      <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1"
        @checked(old('is_active', $coupon?->is_active ?? true))>
      <label class="form-check-label fw-semibold" for="is_active">الكوبون فعال</label>
    </div>
  </div>
</div>

<div class="d-flex flex-column flex-sm-row gap-2 mt-4">
  <button class="btn btn-brand px-4" type="submit">{{ $submitLabel }}</button>
  <a class="btn btn-light px-4" href="{{ route('seller.coupons.index') }}">إلغاء</a>
</div>
