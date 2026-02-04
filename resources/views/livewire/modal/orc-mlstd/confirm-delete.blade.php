<div class="p-4">
  <h5>Confirmar exclusão</h5>
  <p>Tem certeza que deseja excluir este orçamento?</p>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button type="button" class="btn btn-danger" wire:click="delete">Deletar</button>
  </div>
</div>
