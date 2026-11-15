@php
  $statusLabels = [
    'pending' => 'قيد الانتظار',
    'processing' => 'قيد التجهيز',
    'shipped' => 'تم الشحن',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغي',
  ];
@endphp

<div class="dashboard-card card border-0">
  <div class="card-body p-4">
    <h2 class="h5 fw-bold mb-3">تحديث الحالة</h2>

    <form method="POST" action="{{ $action }}">
      @csrf
      @method('PUT')
      <label class="form-label fw-semibold" for="status">الحالة الجديدة</label>
      <select class="form-select" id="status" name="status" required>
        @foreach ($statusLabels as $status => $label)
          <option value="{{ $status }}"
            @selected(old('status', $allowedStatuses[0] ?? $subOrder->status) === $status)
            @disabled(! in_array($status, $allowedStatuses, true))>
            {{ $label }}{{ $status === $subOrder->status ? ' (الحالية)' : '' }}
          </option>
        @endforeach
      </select>
      @if ($allowedStatuses === [])
        <small class="text-muted-custom d-block mt-2">وصل الطلب إلى حالة نهائية ولا يمكن تحديثه.</small>
      @endif
      <button class="btn btn-brand w-100 mt-3" type="submit" @disabled($allowedStatuses === [])>حفظ الحالة</button>
    </form>
  </div>
</div>
