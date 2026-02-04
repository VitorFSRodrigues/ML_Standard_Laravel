@extends('adminlte::page')

@section('title', 'Perguntas')

@section('content_header')
    <h1>Perguntas</h1>
@endsection

@section('content')
    <livewire:powergrid.pergunta-table />
@endsection
