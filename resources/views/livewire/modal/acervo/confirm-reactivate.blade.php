{{-- resources/views/livewire/modal/acervo/confirm-reactivate.blade.php --}}
<div class="p-3">
  <h5 class="mb-3">Tem certeza que deseja Reativar orçamento?</h5>
  <p>O item voltará a ficar <strong>Ativo</strong>.</p>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button class="btn btn-success" wire:click="confirmar">Reativar</button>
  </div>
</div>
