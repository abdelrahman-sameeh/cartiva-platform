@php
  $paymentStatusLabels = [
      'pending' => 'في انتظار الدفع',
      'paid' => 'مدفوع',
      'failed' => 'فشل الدفع',
      'refunded' => 'تم رد المبلغ',
  ];
  $paymentStatusClasses = [
      'pending' => 'text-bg-warning',
      'paid' => 'text-bg-success',
      'failed' => 'text-bg-danger',
      'refunded' => 'text-bg-info',
  ];
@endphp

<span class="badge {{ $paymentStatusClasses[$status] ?? 'text-bg-secondary' }}">
  {{ $paymentStatusLabels[$status] ?? $status }}
</span>
