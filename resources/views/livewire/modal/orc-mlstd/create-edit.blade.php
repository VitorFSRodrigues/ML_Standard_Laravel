<div class="p-4 space-y-3">
  <h5>{{ $orcMLstdId ? 'Editar Orçamento' : 'Novo Orçamento' }}</h5>

    <div class="row">
    <div class="col-md-8 form-group">
        <label>Número do Orçamento</label>
        <input type="text" class="form-control" wire:model.defer="form.numero_orcamento">
        @error('form.numero_orcamento') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>Rev</label>
        <input type="number" min="0" class="form-control" wire:model.defer="form.rev">
        @error('form.rev') <span class="text-danger">{{ $message }}</span> @enderror
    </div>
    </div>
    
  <div class="form-group">
    <label>Orçamentista</label>

    <select class="form-control" wire:model.defer="form.orcamentista_id">
      <option value="">-- selecione --</option>
      @foreach($orcamentistas as $orc)
        <option value="{{ $orc['id'] }}">{{ $orc['nome'] }}</option>
      @endforeach
    </select>

    @error('form.orcamentista_id') <span class="text-danger">{{ $message }}</span> @enderror
  </div>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Fechar</button>
    <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
  </div>
</div>
