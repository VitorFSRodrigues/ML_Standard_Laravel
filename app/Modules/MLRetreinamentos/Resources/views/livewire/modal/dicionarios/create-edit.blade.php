<div class="p-4 space-y-3">
    <h5>{{ $dictItemId ? 'Editar item' : 'Novo item' }}</h5>

    <div class="form-group">
        <label>Dicionario</label>
        <select class="form-control" wire:model.defer="form.dict_key" @if($dictItemId) disabled @endif>
            <option value="">Selecione</option>
            @foreach($this->dictOptions() as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('form.dict_key') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label>Termo</label>
        <input type="text" class="form-control" wire:model.defer="form.Termo">
        @error('form.Termo') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label>Descricao padrao</label>
        <textarea class="form-control" rows="3" wire:model.defer="form.Descricao_Padrao"></textarea>
        @error('form.Descricao_Padrao') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Fechar</button>
        <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
    </div>
</div>
