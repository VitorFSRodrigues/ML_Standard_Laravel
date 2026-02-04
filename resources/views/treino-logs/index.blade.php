@extends('adminlte::page')

@section('title', 'Logs de Treino')

@section('content_header')
    <h1>Logs de Treino</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header"><strong>Atualizações do Treino</strong></div>
        <div class="card-body">
            <livewire:powergrid.treino-logs-table wire:poll.10s />
        </div>
    </div>
@endsection
