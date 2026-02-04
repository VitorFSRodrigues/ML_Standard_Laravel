<div class="d-flex gap-2 align-items-center mb-2">
    <button
        type="button"
        class="btn btn-primary btn-sm"
        wire:click="approveAllFromPage"
    >
        Aprovar Tudo da Página
    </button>

    <span class="text-muted" style="font-size: 12px;">
        (Aprova apenas os itens visíveis nesta página)
    </span>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const collectIds = () => {
            const ids = Array.from(document.querySelectorAll('.pg-rowid'))
                .map(el => parseInt(el.dataset.id))
                .filter(n => !isNaN(n));

            // chama método Livewire com os IDs visíveis
            Livewire.dispatch('captureMlAprovacaoPageIds', { ids });
        };

        // roda ao carregar + sempre que PowerGrid atualizar
        setTimeout(collectIds, 400);

        Livewire.hook('message.processed', (message, component) => {
            setTimeout(collectIds, 250);
        });
    });
</script>
