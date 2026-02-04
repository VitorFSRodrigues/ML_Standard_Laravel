<div class="p-4 space-y-3">
    <h5>Excluir varredura?</h5>
    <p class="mb-0">Esta ação não poderá ser desfeita.</p>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
        <button type="button" class="btn btn-danger" wire:click="delete">Excluir</button>
    </div>
</div>
