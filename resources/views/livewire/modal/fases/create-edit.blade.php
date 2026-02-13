{{-- resources/views/livewire/modal/fases/create-edit.blade.php --}}
<div class="p-3">
  <h5 class="mb-3">{{ $faseId ? 'Editar Fase' : 'Novo Fase' }}</h5>

  <form wire:submit.prevent="save">
    <div class="row">
      <div class="col-md-6 mb-2">
        <label class="form-label">Revisão</label>
        <input type="number" class="form-control" wire:model.defer="form.revisao" min="0">
        @error('form.revisao') <small class="text-danger">{{ $message }}</small> @enderror
      </div>
      <div class="col-md-6 mb-2">
        <label class="form-label">Versão</label>
        <input type="number" class="form-control" wire:model.defer="form.versao" min="1">
        @error('form.versao') <small class="text-danger">{{ $message }}</small> @enderror
      </div>
    </div>

    {{-- 👇 NOVO CAMPO --}}
    <div class="mb-2">
      <label class="form-label">Comentário</label>
      <input type="text"
             class="form-control @error('form.comentario') is-invalid @enderror"
             wire:model.defer="form.comentario"
             maxlength="255"
             placeholder="Observações deste fase (opcional)">
      @error('form.comentario') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
      <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
  </form>
</div>
