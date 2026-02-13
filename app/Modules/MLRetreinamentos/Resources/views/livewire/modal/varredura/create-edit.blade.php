<div class="p-4 space-y-3">
    <h5>{{ $varreduraId ? 'Editar Varredura' : 'Nova Varredura' }}</h5>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Revisão ELE</label>
            <input type="number" min="0" class="form-control" wire:model.defer="form.revisao_ele">
            @error('form.revisao_ele') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="col-md-6 form-group">
            <label>Revisão TUB</label>
            <input type="number" min="0" class="form-control" wire:model.defer="form.revisao_tub">
            @error('form.revisao_tub') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    @if ($varreduraId)
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="d-block">Status ELE</label>
                <input type="checkbox" class="form-check-input" wire:model.defer="form.status_ele">
            </div>
            <div class="col-md-6 form-group">
                <label class="d-block">Status TUB</label>
                <input type="checkbox" class="form-check-input" wire:model.defer="form.status_tub">
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Fechar</button>
        <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
    </div>
</div>
