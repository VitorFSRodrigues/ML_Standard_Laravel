<div class="p-3">

  <h5 class="mb-3">Editar Requisito</h5>

  {{-- Bloco informativo (Triagem) --}}
  <div class="mb-3 p-2 border rounded bg-light">
    <div class="row">
      <div class="col-md-6">
        <strong>Pasta:</strong>
        <div class="text-muted">{{ $triagem['pasta'] }}</div>
      </div>
      <div class="col-md-6">
        <strong>Cliente Final:</strong>
        <div class="text-muted">{{ $triagem['cliente_final'] }}</div>
      </div>

      <div class="col-md-4 mt-2">
        <strong>Tipo de Serviço:</strong>
        <div class="text-muted">{{ $triagem['tipo_servico'] }}</div>
      </div>
      <div class="col-md-4 mt-2">
        <strong>Regime de Contrato:</strong>
        <div class="text-muted">{{ $triagem['regime_contrato'] }}</div>
      </div>
      <div class="col-md-4 mt-2">
        <strong>DDL:</strong>
        <div class="text-muted">{{ $triagem['ddl'] }}</div>
      </div>

      <div class="col-md-12 mt-2">
        <strong>Descrição do Serviço:</strong>
        <div class="text-muted">{{ $triagem['descricao_servico'] }}</div>
      </div>

      <div class="col-md-4 mt-2">
        <strong>Data de início da obra:</strong>
        <div class="text-muted">{{ $triagem['data_inicio_obra'] }}</div>
      </div>
      <div class="col-md-4 mt-2">
        <strong>Prazo:</strong>
        <div class="text-muted">{{ $triagem['prazo_obra'] }}</div>
      </div>
    </div>
  </div>

  {{-- Formulário de edição --}}
  <div class="row">
    <div class="col-md-6 form-group">
      <label>Orçamentista</label>
      <select class="form-control" wire:model.defer="form.orcamentista_id">
        <option value="">Selecione...</option>
        @foreach($optsOrcamentistas as $id => $nome)
          <option value="{{ $id }}">{{ $nome }}</option>
        @endforeach
      </select>
      @error('form.orcamentista_id')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <div class="col-md-3 form-group">
      <label>Qtd. Pico</label>
      <input type="number" min="0" class="form-control" wire:model.defer="form.quantitativo_pico">
      @error('form.quantitativo_pico')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <div class="col-md-3 form-group">
      <label>Regime de trabalho</label>
      <select class="form-control" wire:model.defer="form.regime_trabalho">
        <option value="">Selecione...</option>
        @foreach($optsRegime as $opt)
          <option value="{{ $opt }}">{{ $opt }}</option>
        @endforeach
      </select>
      @error('form.regime_trabalho')<span class="text-danger">{{ $message }}</span>@enderror
    </div>
  </div>

  <div class="row">
    <div class="col-md-3 form-group">
      <label>ICMS (%)</label>
      <input type="number" step="0.01" min="0" max="100" class="form-control" wire:model.defer="form.icms_percent">
      @error('form.icms_percent')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <div class="col-md-4 form-group">
      <label>Conferente Comercial</label>
      <select class="form-control" wire:model.defer="form.conferente_comercial_id">
        <option value="">Selecione...</option>
        @foreach($optsConfComercial as $id => $nome)
          <option value="{{ $id }}">{{ $nome }}</option>
        @endforeach
      </select>
      @error('form.conferente_comercial_id')<span class="text-danger">{{ $message }}</span>@enderror
    </div>

    <div class="col-md-5 form-group">
      <label>Conferente Orçamentista</label>
      <select class="form-control" wire:model.defer="form.conferente_orcamentista_id">
        <option value="">Selecione...</option>
        @foreach($optsConfOrcamentista as $id => $nome)
          <option value="{{ $id }}">{{ $nome }}</option>
        @endforeach
      </select>
      @error('form.conferente_orcamentista_id')<span class="text-danger">{{ $message }}</span>@enderror
    </div>
  </div>

  <div class="form-group">
    <label>Características especiais</label>
    <input type="text" class="form-control" wire:model.defer="form.caracteristicas_especiais" maxlength="255">
    @error('form.caracteristicas_especiais')<span class="text-danger">{{ $message }}</span>@enderror
  </div>

  {{-- Rodapé do modal com botões --}}
  <div class="d-flex justify-content-end gap-2 mt-3">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
  </div>
</div>