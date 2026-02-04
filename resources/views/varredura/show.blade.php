@extends('adminlte::page')

@section('title', 'Varredura #' . $varredura->id)

@section('content_header')
    <h1>Varredura #{{ $varredura->id }}</h1>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header"><strong>Resumo</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Revisão ELE:</strong> {{ $varredura->revisao_ele }}
                </div>
                <div class="col-md-3">
                    <strong>Revisão TUB:</strong> {{ $varredura->revisao_tub }}
                </div>
                <div class="col-md-3">
                    <strong>Status ELE:</strong>
                    <span class="badge {{ $varredura->status_ele ? 'badge-success' : 'badge-secondary' }}">
                        {{ $varredura->status_ele ? 'PRONTO' : 'PENDENTE' }}
                    </span>
                </div>
                <div class="col-md-3">
                    <strong>Status TUB:</strong>
                    <span class="badge {{ $varredura->status_tub ? 'badge-success' : 'badge-secondary' }}">
                        {{ $varredura->status_tub ? 'PRONTO' : 'PENDENTE' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Elétrica (ELE)</strong></div>
        <div class="card-body">
            <div id="pg-wrap-varredura-ele-table" class="pg-scroll">
                <livewire:powergrid.varredura-ele-table :varredura-id="$varredura->id" />
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Tubulação (TUB)</strong></div>
        <div class="card-body">
            <div id="pg-wrap-varredura-tub-table" class="pg-scroll">
                <livewire:powergrid.varredura-tub-table :varredura-id="$varredura->id" />
            </div>
        </div>
    </div>
@endsection
