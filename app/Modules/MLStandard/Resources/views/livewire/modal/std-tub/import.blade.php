<div class="p-3">
  <h5 class="mb-3">Importar STD Tubulação (Excel)</h5>

  <div class="alert alert-info">
    Envie um arquivo <b>.xlsx</b> no mesmo modelo do exportador.
  </div>

  <div class="form-group">
    <label>Arquivo Excel</label>
    <input type="file" class="form-control" wire:model="file">
    @error('file') <span class="text-danger">{{ $message }}</span> @enderror
  </div>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button class="btn btn-primary" wire:click="import">Importar</button>
  </div>
</div>
