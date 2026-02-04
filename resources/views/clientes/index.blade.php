@extends('adminlte::page')
@section('title', 'Clientes (Orçamentos)')
@section('content_header')<h1>Clientes (Orçamentos)</h1>@endsection
@section('content')
    <livewire:powergrid.cliente-orcamento-table />
@endsection