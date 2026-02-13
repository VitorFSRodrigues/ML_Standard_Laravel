@extends('adminlte::page')

@section('title', 'Triagem')

@section('content_header')
    <h1>Triagem</h1>
@endsection

@section('content')
    <livewire:powergrid.triagem-table />
@endsection
