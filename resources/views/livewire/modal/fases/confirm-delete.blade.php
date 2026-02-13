{{-- resources/views/livewire/modal/fases/confirm-delete.blade.php --}}
<div class="p-3">
  <h5 class="mb-3">Excluir Fase</h5>
  <p>Tem certeza que deseja excluir este fase?</p>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button class="btn btn-danger" wire:click="confirmar">Excluir</button>
  </div>
</div>
