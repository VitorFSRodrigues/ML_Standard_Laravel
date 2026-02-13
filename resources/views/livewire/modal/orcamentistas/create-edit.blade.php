<div class="p-3">
  <h5 class="mb-3">{{ $orcamentistaId ? 'Editar Orçamentista' : 'Novo Orçamentista' }}</h5>

  <div class="form-group">
    <label>Nome</label>
    <input type="text" class="form-control" wire:model.defer="form.nome" required>
    @error('form.nome')<span class="text-danger">{{ $message }}</span>@enderror
  </div>

  <div class="form-group">
    <label>E-mail</label>
    <input type="email" class="form-control" wire:model.defer="form.email" required>
    @error('form.email')<span class="text-danger">{{ $message }}</span>@enderror
  </div>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
  </div>
</div>
