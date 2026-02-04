@extends('adminlte::page')

@section('title', 'STD Tubulação')

@section('content_header')
  <h1>STD Tubulação</h1>
@endsection

@section('content')
    <livewire:powergrid.std-tub-table />
@endsection
