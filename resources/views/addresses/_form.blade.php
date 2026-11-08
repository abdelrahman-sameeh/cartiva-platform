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
    <label class="form-label fw-semibold" for="country">الدولة</label>
    <select class="form-select @error('country') is-invalid @enderror" id="country" name="country" required>
      <option value="">اختر الدولة</option>
      @foreach ($countries as $code => $name)
        <option value="{{ $code }}" @selected(old('country', $address?->country) === $code)>{{ $name }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold" for="city">المدينة</label>
    <input class="form-control @error('city') is-invalid @enderror" id="city" name="city" type="text"
      value="{{ old('city', $address?->city) }}" required>
  </div>

  <div class="col-12">
    <label class="form-label fw-semibold" for="street">العنوان بالتفصيل</label>
    <input class="form-control @error('street') is-invalid @enderror" id="street" name="street" type="text"
      value="{{ old('street', $address?->street) }}" placeholder="اسم الشارع ورقم المبنى" required>
  </div>

  <div class="col-12">
    <label class="form-label fw-semibold" for="note">ملاحظات إضافية</label>
    <textarea class="form-control @error('note') is-invalid @enderror" id="note" name="note" rows="3"
      placeholder="مثال: الدور الثالث، الشقة 8">{{ old('note', $address?->note) }}</textarea>
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold" for="latitude">خط العرض <span class="text-muted-custom">(اختياري)</span></label>
    <input class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" type="number"
      step="any" value="{{ old('latitude', $address?->latitude) }}">
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold" for="longitude">خط الطول <span class="text-muted-custom">(اختياري)</span></label>
    <input class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" type="number"
      step="any" value="{{ old('longitude', $address?->longitude) }}">
  </div>

  <div class="col-12">
    <input name="is_default" type="hidden" value="0">
    <div class="form-check form-switch">
      <input class="form-check-input" id="is_default" name="is_default" type="checkbox" value="1"
        @checked(old('is_default', $address?->is_default ?? false))>
      <label class="form-check-label fw-semibold" for="is_default">استخدامه كعنوان افتراضي</label>
    </div>
    <div class="form-text">أول عنوان تضيفه يصبح افتراضيًا تلقائيًا.</div>
  </div>
</div>

<div class="d-flex flex-column flex-sm-row gap-2 mt-4">
  <button class="btn btn-brand px-4" type="submit">{{ $submitLabel }}</button>
  <a class="btn btn-light px-4" href="{{ route('addresses.index') }}">إلغاء</a>
</div>
