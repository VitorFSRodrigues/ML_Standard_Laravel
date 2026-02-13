{{-- resources/views/triagem/show.blade.php --}}
@extends('adminlte::page')

@section('title', 'Triagem Orc.'.$triagem->numero_orcamento)

@section('content_header')
  <div class="d-flex flex-column gap-1">
    <h1 class="mb-2">TRIAGEM DE PROPOSTAS <small class="text-muted">(Probabilidade x Complexidade)</small></h1>

    <div><strong>Nº Orçamento:</strong> {{ $triagem->numero_orcamento }}</div>

    <div>
        <strong>Cliente Final:</strong>
        {{ optional($triagem->clienteFinal)->nome_cliente ?? '—' }}
    </div>

    <div>
        <strong>Endereço do Cliente Final:</strong>
        {{ optional($triagem->clienteFinal)->endereco_completo ?? '—' }}
    </div>

    <div>
      <strong>Local:</strong>
      {{ $triagem->local_formatado !== '' ? $triagem->local_formatado : '—' }}
    </div>

    <div>
        <strong>Data de Criação:</strong>
        {{ optional($triagem->created_at)->format('d/m/Y') }}
    </div>

    <div>
        <strong>Descrição do Serviço:</strong>
        {{ $triagem->descricao_servico ?? '—' }}
    </div>

    <div>
        <strong>Regime de Contrato:</strong>
        {{ $triagem->regime_contrato ?? '—' }}
    </div>
  </div>
@endsection

@section('content')
  {{-- Header/Resumo da triagem --}}
  <livewire:modal.triagem.resumo :triagemId="$triagem->id" />

  <livewire:powergrid.triagem-respostas-table :triagemId="$triagem->id" />
    
@endsection
