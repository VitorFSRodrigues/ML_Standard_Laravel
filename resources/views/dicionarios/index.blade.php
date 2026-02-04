@extends('adminlte::page')

@section('title', 'Dicionarios')

@section('content_header')
    <h1>Dicionarios</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header"><strong>Todos os dicionarios</strong></div>
        <div class="card-body">
            <livewire:powergrid.dicionarios-table />
        </div>
    </div>
@endsection
