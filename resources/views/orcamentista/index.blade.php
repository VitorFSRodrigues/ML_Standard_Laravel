@extends('adminlte::page')

@section('title', 'Orçamentistas')

@section('content_header')
  <h1>Orçamentistas</h1>
@endsection

@section('content')
  <livewire:powergrid.orcamentista-table />
@endsection
