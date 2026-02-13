@extends('adminlte::page')

@section('title', 'Varredura')

@section('content_header')
    <h1>Varredura</h1>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header"><strong>Configurações de Varredura</strong></div>
        <div class="card-body">
            @livewire('powergrid.varredura-table')
        </div>
    </div>
@endsection
