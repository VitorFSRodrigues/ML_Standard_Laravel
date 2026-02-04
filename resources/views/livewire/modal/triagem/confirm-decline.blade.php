{{-- resources/views/livewire/modal/triagem/confirm-decline.blade.php --}}
<div class="p-3">
  <h5 class="mb-3">Tem certeza que deseja Declinar este orçamento?</h5>
  <p>O item será desativado e movido para <strong>Acervo</strong>.</p>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button class="btn btn-danger" wire:click="confirmar">Confirmar</button>
  </div>
</div>
