@extends('adminlte::page')

@section('title', 'Modelos ML')

@section('content_header')
    <h1>Modelos ML</h1>
@endsection

@section('content')
    @livewire('powergrid.modelos-ml-table')
@endsection
