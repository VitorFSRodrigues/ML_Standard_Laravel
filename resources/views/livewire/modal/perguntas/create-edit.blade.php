<div class="p-4 space-y-3">
  <h5>{{ $perguntaId ? 'Editar Pergunta' : 'Nova Pergunta' }}</h5>

  <div class="form-group">
    <label>Descrição</label>
    <input type="text" class="form-control" wire:model.defer="form.descricao">
    @error('form.descricao') <span class="text-danger">{{ $message }}</span> @enderror
  </div>

  <div class="row">
    <div class="col-md-4 form-group">
      <label>Peso</label>
      <input type="number" min="0" class="form-control" wire:model.defer="form.peso">
      @error('form.peso') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="col-md-4 form-group d-flex align-items-end">
      <div class="form-check">
        <input id="padrao" type="checkbox" class="form-check-input" wire:model.defer="form.padrao">
        <label for="padrao" class="form-check-label">Padrão</label>
      </div>
      @error('form.padrao') <span class="text-danger d-block">{{ $message }}</span> @enderror
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Fechar</button>
    <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
  </div>
</div>
