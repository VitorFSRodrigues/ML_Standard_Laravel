@extends('adminlte::page')

@section('title', 'STD Elétrica')

@section('content_header')
  <h1>STD Elétrica</h1>
@endsection

@section('content')
    <livewire:powergrid.std-ele-table />
@endsection
