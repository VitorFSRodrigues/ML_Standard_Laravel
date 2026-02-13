<div class="d-flex gap-2 mb-2">
    <button
        type="button"
        class="btn btn-primary btn-sm"
        wire:click="syncNow"
        wire:loading.attr="disabled"
        title="Puxar do Pipedrive agora"
    >
        <span wire:loading.remove wire:target="syncNow">Sincronizar com Pipedrive</span>
        <span wire:loading wire:target="syncNow">Sincronizando…</span>
    </button>
</div>
