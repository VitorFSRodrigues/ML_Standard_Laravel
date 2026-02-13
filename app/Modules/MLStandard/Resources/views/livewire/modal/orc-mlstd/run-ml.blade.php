<div class="p-4 space-y-3">
  <h5>Executar Machine Learning ({{ $disciplina }})</h5>

  <p class="mb-2">
    Será executado ML da linha <b>{{ $startId }}</b> até a linha <b>{{ $endId }}</b>.
    Você pode ajustar o intervalo antes de confirmar.
  </p>

  <div class="row">
    <div class="col-md-6 form-group">
      <label>Linha inicial (ID)</label>
      <input type="number" min="1" class="form-control" wire:model.defer="startId">
      @error('startId') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="col-md-6 form-group">
      <label>Linha final (ID)</label>
      <input type="number" min="1" class="form-control" wire:model.defer="endId">
      @error('endId') <span class="text-danger">{{ $message }}</span> @enderror
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">
      Cancelar
    </button>

    <button type="button" class="btn btn-success" wire:click="run">
      Confirmar e Executar
    </button>
  </div>
</div>
