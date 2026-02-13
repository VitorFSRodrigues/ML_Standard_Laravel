@extends('adminlte::page')

@section('title', 'Levantamento de Horas')

@section('content_header')
  <h1>Levantamento de Horas - Orçamento {{ $orc->numero_orcamento }} (rev {{ $orc->rev }})</h1>
@endsection

@section('content')
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card mb-3">
    <div class="card-body d-flex gap-2 align-items-center justify-content-between">
      <div>
        <a class="btn btn-outline-primary"
          href="{{ route('mlstandard.orcamentos.template.levantamento') }}">
          Baixar modelo (.xlsx)
        </a>
        <a class="btn btn-outline-success"
          href="{{ route('mlstandard.orcamentos.export.levantamento', ['id' => $orc->id]) }}">
          Exportar preenchidos (.xlsx)
        </a>
      </div>

      <form method="POST"
            action="{{ route('mlstandard.orcamentos.import.levantamento', ['id' => $orc->id]) }}"
            enctype="multipart/form-data"
            class="d-flex gap-2 align-items-center">
        @csrf

        <input type="file" name="arquivo" accept=".xlsx" class="form-control">
        <button type="submit" class="btn btn-primary">Importar</button>
      </form>
    </div>

    @error('arquivo')
      <div class="px-3 pb-3 text-danger">{{ $message }}</div>
    @enderror
  </div>

  <div class="card mb-4">
    <div class="card-header"><strong>Elétrica (ELE)</strong></div>
    <div class="card-body">
      <div id="pg-wrap-levantamento-ele-table" class="pg-scroll">
          <livewire:powergrid.levantamento-ele-table :orcMLstdId="$orc->id" />
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><strong>Tubulação (TUB)</strong></div>
    <div class="card-body">
      <div id="pg-wrap-levantamento-tub-table" class="pg-scroll">
          <livewire:powergrid.levantamento-tub-table :orcMLstdId="$orc->id" />
      </div>
    </div>
  </div>
@endsection
