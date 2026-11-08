@php
  $statusLabels = [
      'pending' => 'قيد الانتظار',
      'processing' => 'قيد التجهيز',
      'shipped' => 'تم الشحن',
      'completed' => 'مكتمل',
      'cancelled' => 'ملغي',
  ];
  $statusClasses = [
      'pending' => 'text-bg-warning',
      'processing' => 'text-bg-info',
      'shipped' => 'text-bg-primary',
      'completed' => 'text-bg-success',
      'cancelled' => 'text-bg-danger',
  ];
@endphp

<span class="badge {{ $statusClasses[$status] ?? 'text-bg-secondary' }}">
  {{ $statusLabels[$status] ?? $status }}
</span>
