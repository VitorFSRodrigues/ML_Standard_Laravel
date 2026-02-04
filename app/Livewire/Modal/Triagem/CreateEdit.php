<?php

namespace App\Livewire\Modal\Triagem;

use App\Enums\TipoServico;
use App\Models\Triagem;
use LivewireUI\Modal\ModalComponent;

class CreateEdit extends ModalComponent
{
    public int|string|null $triagemId = null;

    /** Somente para exibição (campos vindos do Pipedrive) */
    public string $clienteNome = '';
    public string $clienteFinalNome = '';
    public string $numeroOrcamento = '';
    public string $caracteristica = '';
    public string $regimeContrato = '';
    public string $descricaoServico = '';
    public string $cidadeObra = '';
    public string $estadoObra = '';
    public string $paisObra = '';

    /** Editáveis */
    public array $form = [
        'tipo_servico'           => '',
        'descricao_resumida'     => '',
        'condicao_pagamento_ddl' => '',
    ];

    /** Opções do select de Tipo de Serviço */
    public array $optionsTipoServico = [];

    public static function destroyOnClose(): bool { return true; }

    public function mount(int|string|null $triagemId = null): void
    {
        $this->triagemId = $triagemId ? (int)$triagemId : null;

        $t = Triagem::query()
            ->with(['cliente:id,nome_cliente,nome_fantasia', 'clienteFinal:id,nome_cliente,nome_fantasia'])
            ->findOrFail($triagemId);

        // Exibição (somente leitura)
        $this->clienteNome       = $t->cliente?->nome_fantasia ?: ($t->cliente?->nome_cliente ?? '');
        $this->clienteFinalNome  = $t->clienteFinal?->nome_fantasia ?: ($t->clienteFinal?->nome_cliente ?? '');
        $this->numeroOrcamento   = (string) $t->numero_orcamento;
        $this->caracteristica    = (string) ($t->caracteristica_orcamento->value ?? $t->caracteristica_orcamento);
        $this->regimeContrato    = (string) ($t->regime_contrato->value ?? $t->regime_contrato);
        $this->descricaoServico  = (string) $t->descricao_servico;
        $this->cidadeObra        = (string) ($t->cidade_obra ?? '');

        // ✅ Conversão de estado_obra (id -> UF) via config/pipedrive.php
        $estadoRaw = $t->estado_obra; // pode vir int|string|null
        $estadoMap = (array) config('pipedrive.estado_obra_options', []);

        if (is_numeric($estadoRaw)) {
            $estadoId = (int) $estadoRaw;
            $this->estadoObra = (string) ($estadoMap[$estadoId] ?? $estadoRaw);
        } else {
            $this->estadoObra = (string) ($estadoRaw ?? '');
        }

        $this->paisObra = (string) ($t->pais_obra ?? '');

        // Editáveis
        $this->form = [
            'tipo_servico'           => (string) ($t->tipo_servico->value ?? $t->tipo_servico ?? ''),
            'descricao_resumida'     => (string) ($t->descricao_resumida ?? ''),
            'condicao_pagamento_ddl' => (string) ($t->condicao_pagamento_ddl ?? '0'),
        ];

        $this->optionsTipoServico = array_map(fn($e) => $e->value, TipoServico::cases());
    }

    public function save(): void
    {
        $tipos           = array_map(fn($e) => $e->value, TipoServico::cases());

        $data = $this->validate([
            'form.tipo_servico'           => ['required','string','in:'.implode(',', $tipos)],
            'form.descricao_resumida'     => ['required','string','max:255'],
            'form.condicao_pagamento_ddl' => ['required','integer','min:0'],
        ])['form'];

        Triagem::whereKey($this->triagemId)->update($data);

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.triagem.create-edit');
    }
}
