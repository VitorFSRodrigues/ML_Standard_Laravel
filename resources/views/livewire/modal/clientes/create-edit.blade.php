<div class="p-4 space-y-3">
  <h5>{{ $clienteId ? 'Editar Cliente' : 'Novo Cliente' }}</h5>
  <div class="form-group"><label>Nome</label>
    <input type="text" class="form-control" wire:model.defer="form.nome_cliente"></div>
  <div class="form-group"><label>Nome Fantasia</label>
    <input type="text" class="form-control" wire:model.defer="form.nome_fantasia">
  @error('form.nome_fantasia')<span class="text-danger">{{ $message }}</span>@enderror</div>
  <div class="form-group"><label>Endereço Completo</label>
    <input type="text" class="form-control" wire:model.defer="form.endereco_completo"></div>
  <div class="row">
    <div class="col-md-4 form-group"><label>Município</label>
      <input type="text" class="form-control" wire:model.defer="form.municipio"></div>
    <div class="col-md-2 form-group"><label>Estado</label>
      <input type="text" class="form-control" wire:model.defer="form.estado"></div>
    <div class="col-md-3 form-group"><label>País</label>
      <input type="text" class="form-control" wire:model.defer="form.pais"></div>
    <div class="col-md-3 form-group"><label>CNPJ</label>
      <input type="text" class="form-control" wire:model.defer="form.cnpj"></div>
  </div>
  <div class="d-flex justify-content-end gap-2 mt-3">
    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Fechar</button>
    <button type="button" class="btn btn-primary" wire:click="save">Salvar</button>
  </div>
</div>