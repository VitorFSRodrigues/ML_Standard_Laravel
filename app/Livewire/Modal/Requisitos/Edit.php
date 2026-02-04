<?php

namespace App\Livewire\Modal\Requisitos;

use App\Enums\RegimeTrabalho;
use App\Models\{Requisito, Orcamentista, ConferenteComercial, ConferenteOrcamentista, Triagem};
use LivewireUI\Modal\ModalComponent;

class Edit extends ModalComponent
{
    public int $requisitoId;

    // options
    public array $optsOrcamentistas = [];
    public array $optsConfComercial = [];
    public array $optsConfOrcamentista = [];
    public array $optsRegime = [];

    // form
    public array $form = [
        'orcamentista_id'            => null,
        'quantitativo_pico'          => null,
        'regime_trabalho'            => null,
        'icms_percent'               => null, 
        'conferente_comercial_id'    => null,
        'conferente_orcamentista_id' => null,
        'caracteristicas_especiais'  => '',
    ];

    /** Informação somente-leitura da triagem (mostrada no modal) */
    public array $triagem = [];

    public static function destroyOnClose(): bool { return true; }

    public function mount(int $requisitoId): void
    {
        $this->requisitoId = $requisitoId;

        // Carrega Requisito + Triagem + Cliente Final
        /** @var Requisito $r */
        $r = Requisito::with([
            'triagem:id,numero_orcamento,cliente_final_id,caracteristica_orcamento,tipo_servico,regime_contrato,descricao_servico,condicao_pagamento_ddl,data_inicio_obra,prazo_obra,descricao_resumida',
            'triagem.clienteFinal:id,nome_cliente,nome_fantasia',
        ])->findOrFail($requisitoId);
        
        // Aplica fallback só se vier nulo do banco
        $valorIcms = $r->icms_percent ?? config('fiscal.icms_default');

        // Opções dos selects
        $this->optsOrcamentistas = Orcamentista::orderBy('nome')->pluck('nome', 'id')->toArray();
        $this->optsConfComercial = ConferenteComercial::orderBy('nome')->pluck('nome', 'id')->toArray();
        $this->optsConfOrcamentista = ConferenteOrcamentista::orderBy('nome')->pluck('nome', 'id')->toArray();
        $this->optsRegime = RegimeTrabalho::options();

        // Preenche form
        $this->form = [
            'orcamentista_id'            => $r->orcamentista_id,
            'quantitativo_pico'          => $r->quantitativo_pico,
            'regime_trabalho'            => $r->regime_trabalho?->value ?? $r->regime_trabalho,
            'icms_percent'               => number_format((float) $valorIcms, 2, '.', ''),
            'conferente_comercial_id'    => $r->conferente_comercial_id,
            'conferente_orcamentista_id' => $r->conferente_orcamentista_id,
            'caracteristicas_especiais'  => $r->caracteristicas_especiais,
        ];

        // Resumo informativo da triagem (somente leitura)
        $t = $r->triagem;
        $this->triagem = [
            'pasta'               => $this->gerarNomePasta($t),
            'cliente_final'       => $t?->clienteFinal?->nome_fantasia ?? $t?->clienteFinal?->nome_cliente ?? '—',
            'tipo_servico'        => $t?->tipo_servico ?? '—',
            'regime_contrato'     => $t?->regime_contrato ?? '—',
            'descricao_servico'   => $t?->descricao_servico ?? '—',
            'ddl'                 => is_null($t?->condicao_pagamento_ddl) ? '—' : ($t->condicao_pagamento_ddl . ' DDL'),
            'data_inicio_obra'    => $t?->data_inicio_obra ? \Illuminate\Support\Carbon::parse($t->data_inicio_obra)->format('d/m/Y') : '—',
            'prazo_obra'          => is_null($t?->prazo_obra) ? '—' : ($t->prazo_obra . ' dias'),
        ];
    }

    /** Regra do nome da pasta: Orc.XXXX[sufixo]-CLIENTE_ESCOPO */
    private function gerarNomePasta(?\App\Models\Triagem $t): string
    {
        if (!$t) return '—';

        $numero = (string) ($t->numero_orcamento ?? 'XXXX');

        // pega a label “stringzada” da característica
        $label = $t->caracteristica_orcamento?->value
            ?? (string) $t->caracteristica_orcamento
            ?? (string) ($t->caracteristica_orcamento_label ?? '');

        // mapeia label -> sufixo
        $map = [
            'Montagem'   => '',
            'Fabricação' => 'E',
            'FAST'       => 'F',
            'Painéis'    => 'P',
            'Engenharia' => 'Eng',
        ];
        $suf = $map[$label] ?? '';

        // cliente final (fantasia preferencial)
        $cliente = $t->clienteFinal?->nome_fantasia
                ?? $t->clienteFinal?->nome_cliente
                ?? 'CLIENTE';
        // remove espaços e caracteres “problemáticos” para pasta
        $cliente = preg_replace('/[^\pL\pN]+/u', '', $cliente);

        $escopo = (string) ($t->descricao_resumida ?: 'ESCOPO');
        $escopo = preg_replace('/[^\pL\pN]+/u', '', $escopo);

        // monta: Orc.XXXX[“”,F,E,P,Eng]-CLIENTE_ESCOPO
        // (quando sufixo é vazio, não concatena nada)
        $orcPart = 'Orc.' . $numero . $suf;

        return "{$orcPart}-{$cliente}_{$escopo}";
    }

    public function save(): void
    {
        $regimes = RegimeTrabalho::options();

        $data = $this->validate([
            'form.orcamentista_id'            => ['nullable','integer','exists:orcamentistas,id'],
            'form.quantitativo_pico'          => ['nullable','integer','min:0'],
            'form.regime_trabalho'            => ['nullable','string','in:'.implode(',', $regimes)],
            'form.icms_percent'               => ['required','numeric','min:0','max:100'],
            'form.conferente_comercial_id'    => ['nullable','integer','exists:conferente_comercial,id'],
            'form.conferente_orcamentista_id' => ['nullable','integer','exists:conferente_orcamentista,id'],
            'form.caracteristicas_especiais'  => ['nullable','string','max:255'],
        ])['form'];

        Requisito::findOrFail($this->requisitoId)->update($data);

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.requisitos.edit');
    }
}
