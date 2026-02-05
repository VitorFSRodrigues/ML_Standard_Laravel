@extends('adminlte::page')

@section('title', 'Evidencias de Uso')

@section('content_header')
    <h1>Evidencias de Uso</h1>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <strong>Tempo Medio de Levantamento sem ML (min)</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('orc-mlstd.evidencias-uso.tempos.update') }}">
                @csrf

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="tempo_levantamento_ele_min">ELE (min)</label>
                        <input
                            id="tempo_levantamento_ele_min"
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="tempo_levantamento_ele_min"
                            value="{{ old('tempo_levantamento_ele_min', $tempoEleMin) }}"
                            class="form-control @error('tempo_levantamento_ele_min') is-invalid @enderror"
                            required
                        >
                        @error('tempo_levantamento_ele_min')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group col-md-3">
                        <label for="tempo_levantamento_tub_min">TUB (min)</label>
                        <input
                            id="tempo_levantamento_tub_min"
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="tempo_levantamento_tub_min"
                            value="{{ old('tempo_levantamento_tub_min', $tempoTubMin) }}"
                            class="form-control @error('tempo_levantamento_tub_min') is-invalid @enderror"
                            required
                        >
                        @error('tempo_levantamento_tub_min')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Atualizar tempos
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Evidencias de Uso do ML</strong>
        </div>
        <div class="card-body">
            <livewire:powergrid.evidencias-uso-ml-table
                :tempoEleMin="$tempoEleMin"
                :tempoTubMin="$tempoTubMin"
            />
        </div>
    </div>
@endsection

