<?php

namespace App\Livewire\Powergrid;

use App\Models\Triagem;
use App\Enums\DestinoTriagem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use Illuminate\Contracts\View\View;
use PowerComponents\LivewirePowerGrid\Components\Rules\RuleManager;
use App\Jobs\RunPipedriveSync;

final class TriagemTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
        'mover-triagem' => 'mover',
        'triagem-sync-now' => 'syncNow',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    // ID do grid para refresh
    public string $tableName = 'triagem-table';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('components.triagem.header-actions'),
            PowerGrid::footer()
                ->showPerPage(50)
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        // Eager loading de clientes para mostrar nomes nas colunas
        return Triagem::query()
            ->naTriagem()
            ->with(['cliente:id,nome_cliente,nome_fantasia', 'clienteFinal:id,nome_cliente,nome_fantasia'])
            ->orderBy('id', 'desc');
    }

    public function relationSearch(): array
    {
        return [
            'clienteFinal' => ['nome_cliente', 'nome_fantasia'],
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('nome_pasta')
                ->placeholder('Pasta')
                ->builder(function (Builder $query, $data) {
                    // PowerGrid pode mandar string OU array (ex.: ['value' => '...'])
                    $value = is_array($data) ? (string) ($data['value'] ?? '') : (string) $data;
                    $value = trim($value);

                    if ($value === '') {
                        return $query;
                    }

                    // Normaliza para permitir filtrar pela string final exibida:
                    // "Orc.8215F-ALPEK_SERV" -> tokens ["8215F","ALPEK","SERV"]
                    $normalized = str_ireplace(['orc.', 'orc'], '', $value);
                    $tokens = preg_split('/[\s\-_]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

                    return $query->where(function ($q) use ($tokens) {
                        foreach ($tokens as $token) {
                            $token = trim($token);
                            if ($token === '') continue;

                            $like = '%' . $token . '%';
                            $upper = mb_strtoupper($token);

                            $q->where(function ($qq) use ($like, $upper) {
                                // Campos que compõem a "Pasta"
                                $qq->where('numero_orcamento', 'like', $like)
                                ->orWhere('descricao_resumida', 'like', $like)
                                ->orWhere('descricao_servico', 'like', $like)
                                ->orWhereHas('cliente', function ($c) use ($like) {
                                    $c->where('nome_fantasia', 'like', $like)
                                        ->orWhere('nome_cliente', 'like', $like);
                                });

                                // Se o usuário digitar sufixos do "Pasta" (F/E/P/Eng), tenta casar com a característica
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

            Filter::inputText('cliente_final_nome')
                ->placeholder('Cliente Final')
                ->filterRelation('clienteFinal', 'nome_cliente'),

            Filter::inputText('caracteristica_orcamento_text')
                ->placeholder('Característica')
                ->builder(function (Builder $query, $data) {
                    $value = is_array($data) ? (string)($data['value'] ?? '') : (string)$data;
                    $value = trim($value);
                    if ($value === '') return $query;

                    return $query->where('caracteristica_orcamento', 'like', "%{$value}%");
                }),

            Filter::inputText('regime_contrato_text')
                ->placeholder('Regime Contrato')
                ->builder(function (Builder $query, $data) {
                    $value = is_array($data) ? (string)($data['value'] ?? '') : (string)$data;
                    $value = trim($value);
                    if ($value === '') return $query;

                    return $query->where('regime_contrato', 'like', "%{$value}%");
                }),
            Filter::inputText('descricao_servico')->placeholder('Desc. Serviço'),
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
            ->add('clienteFinal.nome_cliente')  // via with()  
            ->add('cliente_final_nome', fn($row) => (string) optional($row->clienteFinal)->nome_cliente)
         
            ->add('numero_orcamento')

            // 🔽 transforma enums em string
            // ->add('caracteristica_orcamento_label', function ($row) {
            //     return $row->caracteristica_orcamento?->value ?? (string) $row->caracteristica_orcamento;
            // })
            ->add('caracteristica_orcamento_text', fn($row) =>
                (string) ($row->caracteristica_orcamento?->value ?? $row->caracteristica_orcamento ?? '')
            )

            ->add('tipo_servico_label', function ($row) {
                return $row->tipo_servico?->value ?? (string) $row->tipo_servico;
            })

            // ->add('regime_contrato_label', fn($row) =>
            //     $row->regime_contrato?->value ?? (string)$row->regime_contrato
            // )
            ->add('regime_contrato_text', fn($row) =>
                (string) ($row->regime_contrato?->value ?? $row->regime_contrato ?? '')
            )

            ->add('nome_pasta', function ($row) {
                // pega a label já “stringzada”
                $label = (string) ($row->caracteristica_orcamento?->value ?? $row->caracteristica_orcamento_text ?? '');

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
            Column::make('Cliente Final', 'cliente_final_nome')->searchable()->sortable(),

            // Column::make('Nº Orçamento', 'numero_orcamento')->searchable()->sortable(),

            // 🔽 use os labels-string (não os campos originais em enum)
            Column::make('Característica', 'caracteristica_orcamento_text', 'caracteristica_orcamento')->searchable()->sortable(),
            // Column::make('Tipo serviço', 'tipo_servico_label')->searchable()->sortable(),
            Column::make('Regime contrato', 'regime_contrato_text', 'regime_contrato')->searchable()->sortable(),

            Column::make('Desc. Serviço', 'descricao_servico')->searchable(),
            // Column::make('Desc. Resumida', 'descricao_resumida')->searchable(),
            Column::make('DDL', 'condicao_pagamento_ddl')->sortable(),
            //Column::make('Created at', 'created_at_formatted', 'created_at')
            //    ->sortable(),
            // Column::make('Status', 'status')->toggleable(false, 'Ativo', 'Inativo') // só exibe rótulos
            //     ->searchable(),
            Column::action('Ações')
        ];
    }

    public function header(): array
    {
        return [];
    }

    public function syncNow(): void
    {
        // Enfileira a sincronização (pode ter parâmetros se quiser)
        RunPipedriveSync::dispatch();

        // Feedback e refresh da grid
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Sincronização enfileirada. As novas entradas aparecerão em instantes.',
        ]);

        // Atualiza a tabela (id do seu grid)
        $this->dispatch('pg:eventRefresh', id: $this->tableName);
    }

    public function actions($row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.triagem.create-edit', ['triagemId' => $row->id]),

            Button::add('view')
                ->slot('Triagem')
                ->class('btn btn-xs btn-success')
                ->route('triagem.show', ['triagem' => $row->id]), // substitui 'id' pelo id da linha
            
            Button::add('mover')
                ->slot('Mover')
                ->class('btn btn-xs btn-info')
                ->openModal('modal.triagem.move', [
                            'triagemId' => $row->id,
                            'atual'     => DestinoTriagem::TRIAGEM->value, // destino atual (ajuda no select do modal)
                            ]),
            Button::add('desativar')
                ->slot('Declinar')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.triagem.confirm-decline', [
                    'triagemId' => $row->id,
                ]),
        ];
    }

    public function mover(int $id, string $destino): void
    {
        Triagem::whereKey($id)->update([
            'destino'  => $destino,
            'moved_at' => now(),
            'moved_by' => Auth::id(), // se tiver auth
        ]);

        // Atualiza a grid atual
        $this->dispatch('pg:eventRefresh', id: $this->tableName);
    }

    public function actionRules($row): array
    {
        return [
            // Oculta "Mover" se incompleta ou desativada
            (new RuleManager())
                ->button('mover')
                ->when(fn ($row) => !$this->triagemHabilitada($row))
                ->hide(),

            // Oculta "Triagem" (o botão se chama 'view')
            (new RuleManager())
                ->button('view')
                ->when(fn ($row) => !$this->triagemHabilitada($row))
                ->hide(),

            // Se já está desativado, oculta "Declinar"
            (new RuleManager())
                ->button('desativar')
                ->when(fn ($row) => empty($row->status)) // 0/false tratado como vazio
                ->hide(),
        ];
    }

    /** Habilita botões se completo + ativo */
    private function triagemHabilitada($row): bool
    {
        return $this->triagemCompleta($row) && !empty($row->status);
    }

    private function triagemCompleta($row): bool
    {
        // tipo_servico pode ser enum ou string – trate ambos
        $tipoOk = !empty($row->tipo_servico) || !empty($row->tipo_servico_label);

        return $tipoOk
            && !empty($row->descricao_resumida)
            && isset($row->condicao_pagamento_ddl)    // pode ser 0, então use isset
            && $row->condicao_pagamento_ddl !== '';   // evita string vazia
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert('.$rowId.')');
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
