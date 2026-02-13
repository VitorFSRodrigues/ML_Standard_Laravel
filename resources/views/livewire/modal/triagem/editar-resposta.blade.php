<div class="p-4">
  <h5>Alterar Resposta</h5>

  <div class="form-group mt-3">
    <label>Resposta</label>
    <select class="form-control" wire:model="resposta">
      <option value="V">V</option>
      <option value="F">F</option>
      <option value="NA">NA</option>
    </select>
  </div>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
    <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
  </div>
</div>
