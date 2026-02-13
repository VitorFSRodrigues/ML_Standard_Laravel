<?php

namespace App\Livewire\Powergrid;

use App\Enums\DestinoTriagem;
use App\Models\Triagem;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Components\Rules\RuleManager;

final class AcervoTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
        'mover-triagem' => 'mover',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }
    public string $tableName = 'acervo-table';

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
            ->noAcervo()    // <—
            ->with(['cliente:id,nome_cliente,nome_fantasia', 'clienteFinal:id,nome_cliente,nome_fantasia'])
            ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [
            'clienteFinal' => ['nome_cliente', 'nome_fantasia'],
            'cliente' => ['nome_cliente', 'nome_fantasia'],
        ];
    }

    public function filters(): array
    {
        return [
            // ✅ Pasta (campo calculado) -> precisa builder()
            Filter::inputText('nome_pasta')
                ->placeholder('Pasta')
                ->builder(function (Builder $query, $data) {
                    $value = is_array($data) ? (string)($data['value'] ?? '') : (string)$data;
                    $value = trim($value);
                    if ($value === '') return $query;

                    $normalized = str_ireplace(['orc.', 'orc'], '', $value);
                    $tokens = preg_split('/[\s\-_]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

                    return $query->where(function ($q) use ($tokens) {
                        foreach ($tokens as $token) {
                            $like = '%' . $token . '%';
                            $upper = mb_strtoupper($token);

                            $q->where(function ($qq) use ($like, $upper) {
                                $qq->where('numero_orcamento', 'like', $like)
                                ->orWhere('descricao_resumida', 'like', $like)
                                ->orWhere('descricao_servico', 'like', $like)
                                ->orWhereHas('cliente', function ($c) use ($like) {
                                    $c->where('nome_fantasia', 'like', $like)
                                        ->orWhere('nome_cliente', 'like', $like);
                                });

                                $mapSufixo = [
                                    'F'   => 'FAST',
                                    'E'   => 'Fabricação',
                                    'P'   => 'Painéis',
                                    'ENG' => 'Engenharia',
                                ];

                                if (isset($mapSufixo[$upper])) {
                                    $qq->orWhere('caracteristica_orcamento', $mapSufixo[$upper]);
                                }
                            });
                        }
                    });
                }),

            // ✅ Cliente Final
            Filter::inputText('cliente_final_nome')
                ->placeholder('Cliente Final')
                ->builder(function (Builder $query, $data) {
                    $value = is_array($data) ? (string)($data['value'] ?? '') : (string)$data;
                    $value = trim($value);
                    if ($value === '') return $query;

                    return $query->whereHas('clienteFinal', function ($q) use ($value) {
                        $q->where('nome_fantasia', 'like', "%{$value}%")
                        ->orWhere('nome_cliente', 'like', "%{$value}%");
                    });
                }),

            // ✅ Tipo serviço (filtra no campo real)
            Filter::inputText('tipo_servico_text')
                ->placeholder('Tipo serviço')
                ->builder(function (Builder $query, $data) {
                    $value = is_array($data) ? (string)($data['value'] ?? '') : (string)$data;
                    $value = trim($value);
                    if ($value === '') return $query;

                    return $query->where('tipo_servico', 'like', "%{$value}%");
                }),

            // ✅ Regime contrato (filtra no campo real)
            Filter::inputText('regime_contrato_text')
                ->placeholder('Regime contrato')
                ->builder(function (Builder $query, $data) {
                    $value = is_array($data) ? (string)($data['value'] ?? '') : (string)$data;
                    $value = trim($value);
                    if ($value === '') return $query;

                    return $query->where('regime_contrato', 'like', "%{$value}%");
                }),

            // ✅ Desc. Serviço (campo real)
            Filter::inputText('descricao_servico')->placeholder('Desc. Serviço'),

            // ✅ Status (se quiser filtrar)
            Filter::select('status')
                ->dataSource([
                    ['id' => 1, 'name' => 'Ativo'],
                    ['id' => 0, 'name' => 'Inativo'],
                ])
                ->optionLabel('name')
                ->optionValue('id'),

            Filter::datetimepicker('created_at'),
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('cliente_id')
            ->add('cliente_final_id')
            ->add('cliente.nome_cliente')       // via with()
            ->add('cliente_final_nome', fn($row) =>
                (string) (optional($row->clienteFinal)->nome_fantasia
                    ?? optional($row->clienteFinal)->nome_cliente
                    ?? '')
            )          
            ->add('numero_orcamento')

            // 🔽 transforma enums em string
            ->add('caracteristica_orcamento_label', function ($row) {
                return $row->caracteristica_orcamento?->value ?? (string) $row->caracteristica_orcamento;
            })
            ->add('tipo_servico_text', fn($row) =>
                (string) ($row->tipo_servico?->value ?? $row->tipo_servico ?? '')
            )

            ->add('regime_contrato_text', fn($row) =>
                (string) ($row->regime_contrato?->value ?? $row->regime_contrato ?? '')
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
            ->add('status')
            ->add('created_at_formatted', fn (Triagem $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('Pasta', 'nome_pasta')->searchable()->sortable(),

            //Column::make('Id', 'id')->sortable(),
            // Column::make('Cliente', 'cliente.nome_cliente', 'cliente_id')
            //     ->searchable()->sortable(),
            Column::make('Cliente Final', 'cliente_final_nome', 'cliente_final_id')->searchable()->sortable(),

            // Column::make('Nº Orçamento', 'numero_orcamento')->searchable()->sortable(),

            // 🔽 use os labels-string (não os campos originais em enum)
            // Column::make('Característica', 'caracteristica_orcamento_label')->searchable()->sortable(),
            Column::make('Tipo serviço', 'tipo_servico_text', 'tipo_servico')->searchable()->sortable(),

            Column::make('Regime contrato', 'regime_contrato_text', 'regime_contrato')->searchable()->sortable(),

            Column::make('Desc. Serviço', 'descricao_servico')->searchable(),
            // Column::make('Desc. Resumida', 'descricao_resumida')->searchable(),
            // Column::make('DDL', 'condicao_pagamento_ddl')->sortable(),
            // Column::make('Início', 'data_inicio_obra')->sortable(),
            // Column::make('Prazo (dias)', 'prazo_obra')->sortable(),
            Column::make('Status', 'status')->toggleable(false, 'Ativo', 'Inativo') // só exibe rótulos
                ->searchable(),

            //Column::make('Created at', 'created_at_formatted', 'created_at')
            //    ->sortable(),

            Column::action('Ações')
        ];
    }

    public function actions(Triagem $row): array
    {
        return [
            Button::add('mover')
                ->slot('Mover')
                ->class('btn btn-xs btn-info')
                ->openModal('modal.triagem.move', [
                    'triagemId' => $row->id,
                    'atual'     => DestinoTriagem::ACERVO->value,
                ]),
            Button::add('reativar')
                ->slot('Reativar')
                ->class('btn btn-xs btn-success')
                ->openModal('modal.acervo.confirm-reactivate', [
                    'triagemId' => $row->id,
                ]),
            Button::add('desativar')
                ->slot('Declinar')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.triagem.confirm-decline', [ // reutilizando o mesmo modal
                    'triagemId' => $row->id,
                ]),
        ];
    }

    public function actionRules($row): array
    {
        return [
            (new RuleManager())
                ->button('mover')
                ->when(fn($row) => $row->status === false)
                ->hide(),

            (new RuleManager())
                ->button('reativar')
                ->when(fn($row) => $row->status === true)
                ->hide(),
            // "Desativar" só aparece quando status=true
            (new RuleManager())
                ->button('desativar')
                ->when(fn($row) => $row->status === false)
                ->hide(),
        ];
    }
    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}
