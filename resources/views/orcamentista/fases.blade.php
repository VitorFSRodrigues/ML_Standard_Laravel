{{-- resources/views/orcamentista/fases.blade.php --}}
@extends('adminlte::page')

@section('title', "Fases – Orçamentista {$orcamentista->nome}")

@section('content_header')
  <h1>Fases – Orçamentista: {{ $orcamentista->nome }}</h1>

  @if($triagem)
    @php
      $carac = $triagem->caracteristica_orcamento?->value ?? (string) $triagem->caracteristica_orcamento;
    @endphp
    <div class="text-sm text-muted">
      Orçamento: <strong>{{ $triagem->numero_orcamento }}</strong> —
      Cliente Final:
      <strong>{{ $triagem->clienteFinal?->nome_fantasia ?? $triagem->clienteFinal?->nome_cliente }}</strong><br>
      Característica: <strong>{{ $carac }}</strong><br>
      Descrição: {{ $triagem->descricao_servico }}
    </div>
  @else
    <div class="text-sm text-warning">
      Nenhuma triagem selecionada. Volte à lista e clique em <strong>Orçar</strong>.
    </div>
  @endif
@endsection

@section('content')
  @if($triagem)
    <livewire:powergrid.fases-table :triagemId="$triagemId" />
  @endif
@endsection
