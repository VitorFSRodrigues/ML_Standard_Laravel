<div class="p-4 space-y-3">
    <h5>Pronto para treino ({{ $disciplina }})</h5>
    <p class="mb-0">Tem certeza que deseja marcar pronto? Não será possível voltar.</p>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
        <button type="button" class="btn btn-success" wire:click="confirm">Confirmar</button>
    </div>
</div>
