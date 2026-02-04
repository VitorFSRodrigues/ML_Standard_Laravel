<div class="p-3 space-y-3">
    <h5 class="mb-2">Editar Triagem</h5>

    {{-- BLOCO: Dados do Pipedrive (somente leitura) --}}
    <div class="border rounded p-3">
        <div class="fw-bold mb-2">Dados do Pipedrive (somente leitura)</div>

        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label">Cliente</label>
                <input class="form-control" type="text" value="{{ $clienteNome }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Cliente Final</label>
                <input class="form-control" type="text" value="{{ $clienteFinalNome }}" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nº Orçamento</label>
                <input class="form-control" type="text" value="{{ $numeroOrcamento }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Característica</label>
                <input class="form-control" type="text" value="{{ $caracteristica }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Reg. Contrato</label>
                <input class="form-control" type="text" value="{{ $regimeContrato }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Cidade da Obra</label>
                <input class="form-control" type="text" value="{{ $cidadeObra }}" readonly>
            </div>

            <div class="col-md-4">
                <label class="form-label">Estado da Obra</label>
                <input class="form-control" type="text" value="{{ $estadoObra }}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">País da Obra</label>
                <input class="form-control" type="text" value="{{ $paisObra }}" readonly>
            </div>
            <div class="col-md-12">
                <label class="form-label">Descrição do Serviço</label>
                <input class="form-control" type="text" value="{{ $descricaoServico }}" readonly>
            </div>
        </div>
    </div>

    {{-- BLOCO: Complementos (editáveis) --}}
    <div class="border rounded p-3">
        <div class="fw-bold mb-2">Complementos (editáveis)</div>

        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label">Tipo de Serviço</label>
                <select class="form-select" wire:model.defer="form.tipo_servico">
                    <option value="">—</option>
                    @foreach($optionsTipoServico as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
                @error('form.tipo_servico') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Cond. Pag. (DDL)</label>
                <input class="form-control" type="number" min="0" wire:model.defer="form.condicao_pagamento_ddl">
                @error('form.condicao_pagamento_ddl') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="col-md-12">
                <label class="form-label">Descrição resumida (p/ pasta)</label>
                <input class="form-control" type="text" wire:model.defer="form.descricao_resumida">
                @error('form.descricao_resumida') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-secondary" wire:click="$dispatch('closeModal')">Cancelar</button>
        <button class="btn btn-primary" wire:click="save">Salvar</button>
    </div>
</div>

