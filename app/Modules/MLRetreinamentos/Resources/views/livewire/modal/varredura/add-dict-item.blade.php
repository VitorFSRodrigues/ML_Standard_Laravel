<div class="p-4 space-y-3">
    <h5>Adicionar Item Dicionário ({{ $disciplina }})</h5>

    <div class="form-group">
        <label>Dicionário</label>
        <select class="form-control" wire:model.defer="dict">
            <option value="">-- selecione --</option>
            @foreach($this->dictOptions() as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('dict') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label>Termo</label>
        <textarea class="form-control" rows="2" wire:model.defer="termo"></textarea>
        @error('termo') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label>Descrição Padrão</label>
        <textarea class="form-control" rows="3" wire:model.defer="descricaoPadrao"></textarea>
        @error('descricaoPadrao') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Fechar</button>
        <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
    </div>
</div>
