{{-- resources/views/livewire/triagem/resumo.blade.php --}}
<div class="mt-3">
  <div class="d-flex justify-content-between align-items-center p-2 rounded border bg-light">
    <div>
      <strong>Nível:</strong>
      @php
        $badge = $nivel === 'Alta' ? 'badge-danger'
               : ($nivel === 'Média' ? 'badge-warning' : 'badge-success');
      @endphp
      <span class="badge {{ $badge }}">{{ $nivel }}</span>

      <div class="mt-1">
        <strong>Probabilidade:</strong> {{ $probabilidade }}
      </div>
    </div>

    <div>
      <strong>Soma do Sub-total:</strong>
      <span>{{ number_format($total, 2, ',', '.') }}</span>
    </div>
  </div>
</div>
