{{-- resources/views/orcamentista/show.blade.php --}}
@extends('adminlte::page')

@section('title', "Orçamentista: {$orcamentista->nome}")

@section('content_header')
  <h1>Orçamentista: {{ $orcamentista->nome }}</h1>
  {{-- <span class="text-muted">{{ $orcamentista->email }}</span> --}}
@endsection

@section('content')
  <livewire:powergrid.orcamentista-id-table :orcamentistaId="$orcamentista->id" />
@endsection
