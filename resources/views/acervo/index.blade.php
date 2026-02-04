{{-- resources/views/acervo/index.blade.php --}}
@extends('adminlte::page')

@section('title','Acervo')

@section('content_header')
  <h1>Acervo</h1>
@endsection

@section('content')
  <livewire:powergrid.acervo-table />
@endsection
