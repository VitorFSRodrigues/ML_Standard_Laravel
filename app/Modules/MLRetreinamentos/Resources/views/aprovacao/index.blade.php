@extends('adminlte::page')

@section('title', 'ML Treino')

@section('content_header')
    <h1>ML Treino - Aprovação para Retreino</h1>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <div><b>Modo Treinador:</b> itens reprovados de todos os orçamentos.</div>
            <div class="text-muted">
                Por padrão mostramos apenas <b>REPROVADOS</b>. Você pode habilitar "Mostrar Aprovados".
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><b>Elétrica (ELE)</b></div>
        <div class="card-body">
            @livewire('powergrid.aprovacao-ele-global-table')
        </div>
    </div>

    <div class="card">
        <div class="card-header"><b>Tubulação (TUB)</b></div>
        <div class="card-body">
            @livewire('powergrid.aprovacao-tub-global-table')
        </div>
    </div>
@endsection
