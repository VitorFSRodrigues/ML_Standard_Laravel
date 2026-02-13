<?php

namespace App\Livewire\Powergrid;

use App\Enums\DestinoTriagem;
use App\Models\Triagem;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Components\Rules\RuleManager;

final class OrcamentistaIdTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    public string $tableName = 'orcamentista-itens';

      /** Livewire seta como string; deixe string|int e caste quando usar */
    public int|string $orcamentistaId;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage(20)->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Triagem::query()
            ->emOrcamento((int) $this->orcamentistaId)
            ->with(['cliente:id,nome_cliente,nome_fantasia', 'clienteFinal:id,nome_cliente'])
            ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('cliente_id')
            ->add('cliente_final_id')
            ->add('cliente.nome_cliente')       // via with()
            ->add('clienteFinal.nome_cliente')  // via with()            
            ->add('numero_orcamento')

            // 🔽 transforma enums em string
            ->add('caracteristica_orcamento_label', function ($row) {
                return $row->caracteristica_orcamento?->value ?? (string) $row->caracteristica_orcamento;
            })
            ->add('tipo_servico_label', function ($row) {
                return $row->tipo_servico?->value ?? (string) $row->tipo_servico;
            })

            ->add('regime_contrato_label', fn($row) =>
                $row->regime_contrato?->value ?? (string)$row->regime_contrato
            )

            ->add('nome_pasta', function ($row) {
                // pega a label já “stringzada”
                $label = $row->caracteristica_orcamento?->value
                    ?? (string) $row->caracteristica_orcamento
                    ?? (string) ($row->caracteristica_orcamento_label ?? '');

                // mapeia para o sufixo desejado
                $sufixo = match ($label) {
                    'FAST'       => 'F',
                    'Fabricação' => 'E',
                    'Painéis'    => 'P',
                    'Engenharia' => 'Eng',
                    'Montagem'   => '',        // vazio
                    default      => '',        // fallback seguro
                };

                $orc     = trim((string) $row->numero_orcamento);
                $cliente = trim(optional($row->cliente)->nome_fantasia ?? '');
                $escopo  = trim((string) ($row->descricao_resumida ?? ''));

                // monta: Orc.XXXX[“”,F,E,P,Eng]-CLIENTE_ESCOPO
                // quando sufixo é vazio, não concatena nada
                $orcPart = 'Orc.' . $orc . $sufixo;

                return "{$orcPart}-{$cliente}_{$escopo}";
            })

            ->add('descricao_servico')
            ->add('descricao_resumida')
            ->add('condicao_pagamento_ddl')

            ->add('data_inicio_obra_fmt', fn ($row) => optional($row->data_inicio_obra)->format('d/m/Y') ?? '')
            ->add('prazo_obra')
            ->add('created_at_formatted', fn (Triagem $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('Pasta', 'nome_pasta')->searchable()->sortable(),

            //Column::make('Id', 'id')->sortable(),
            // Column::make('Cliente', 'cliente.nome_cliente', 'cliente_id')
            //     ->searchable()->sortable(),
            Column::make('Cliente Final', 'clienteFinal.nome_cliente', 'cliente_final_id')
                ->searchable()->sortable(),

            // Column::make('Nº Orçamento', 'numero_orcamento')->searchable()->sortable(),

            // 🔽 use os labels-string (não os campos originais em enum)
            // Column::make('Característica', 'caracteristica_orcamento_label')->searchable()->sortable(),
            // Column::make('Tipo serviço', 'tipo_servico_label')->searchable()->sortable(),
            Column::make('Regime contrato', 'regime_contrato_label')->searchable()->sortable(),

            Column::make('Desc. Serviço', 'descricao_servico')->searchable(),
            // Column::make('Desc. Resumida', 'descricao_resumida')->searchable(),
            // Column::make('DDL', 'condicao_pagamento_ddl')->sortable(),
            // Column::make('Início', 'data_inicio_obra')->sortable(),
            // Column::make('Prazo (dias)', 'prazo_obra')->sortable(),
            //Column::make('Created at', 'created_at_formatted', 'created_at')
            //    ->sortable(),

            Column::action('Ações')
        ];
    }

    public function actions(Triagem $row): array
    {
         return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.triagem.create-edit', ['triagemId' => $row->id]),

            Button::add('mover')
                ->slot('Mover')
                ->class('btn btn-xs btn-info')
                ->openModal('modal.triagem.move', [
                    'triagemId'           => $row->id,
                    'atual'               => 'orcamento',           // estamos na tela do orçamentista
                    'orcamentistaAtualId' => $row->orcamentista_id, // para pré-selecionar/validar
                ]),
            Button::add('desativar')
                ->slot('Declinar')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.triagem.confirm-decline', [
                    'triagemId' => $row->id,   // << id da triagem
                ]),
            Button::add('orcar')
                ->slot('Orçar')
                ->class('btn btn-xs btn-success')
                ->route('orcamentista.fases', [
                        'orcamentista' => $this->orcamentistaId, // do componente
                        'triagemId'    => $row->id,              // vai virar query string
                    ], '_self'),
        ];
    }

    public function actionRules($row): array
    {
        return [
            // Se já está desativado, oculta o "Desativar"
            (new RuleManager())
                ->button('desativar')
                ->when(fn($row) => $row->status === false)
                ->hide(),
        ];
    }
}
