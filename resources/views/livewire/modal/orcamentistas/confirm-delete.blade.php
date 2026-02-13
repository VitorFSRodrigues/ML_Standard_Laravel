<div class="p-3">
  <h5 class="mb-3">Excluir Orçamentista</h5>

  <p>Tem certeza que deseja excluir
     <strong>{{ $registro?->nome }}</strong>
     ({{ $registro?->email }})?</p>

  <div class="d-flex justify-content-end gap-2">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button type="button" class="btn btn-danger" wire:click="delete">Excluir</button>
  </div>
</div>
