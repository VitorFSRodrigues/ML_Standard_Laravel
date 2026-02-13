<?php

namespace App\Livewire\Powergrid;

use App\Models\Requisito;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class RequisitoTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    public string $tableName = 'requisitos-table';

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
        return Requisito::query()
            ->with([
            'triagem:id,numero_orcamento,descricao_servico,cliente_final_id,descricao_resumida',
            'triagem.clienteFinal:id,nome_fantasia,nome_cliente',
            'orcamentista:id,nome',
            'conferenteComercial:id,nome',
            'conferenteOrcamentista:id,nome',
            ])
        ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [
            // 'triagem' => [
            //     'clienteFinal' => ['nome_fantasia', 'nome_cliente'],
            // ],
            'triagem' => ['numero_orcamento', 'descricao_servico'],
            'orcamentista' => ['nome'],
            // cliente final é relação dentro de triagem, então tratamos via builder no filtro
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('triagem_id')
            ->add('triagem.numero_orcamento')
            ->add('triagem.descricao_servico')
            ->add('cliente_final_nome', fn($r) =>
                        $r->triagem?->clienteFinal?->nome_fantasia
                        ?? $r->triagem?->clienteFinal?->nome_cliente
                        ?? ''
                    )
            ->add('orcamentista.nome', fn($r) => $r->orcamentista->nome ?? '')
            ->add('quantitativo_pico')
            ->add('regime_trabalho_label', fn($r) => (string) ($r->regime_trabalho?->value ?? $r->regime_trabalho ?? ''))
            ->add('icms_percent_fmt', fn($r) => number_format((float)$r->icms_percent, 2, ',', '.').' %')
            ->add('conferente_comercial_nome', fn($r) => $r->conferenteComercial->nome ?? '')
            ->add('conferente_orcamentista_nome', fn($r) => $r->conferenteOrcamentista->nome ?? '')
            ->add('caracteristicas_especiais');
    }

    public function columns(): array
    {
        return [
            // Column::make('ID', 'id')->sortable(),
            Column::make('N.Orc.', 'triagem.numero_orcamento', 'triagem_id')->sortable()->searchable(),
            Column::make('Descrição Serviço', 'triagem.descricao_servico', 'triagem_id')->sortable()->searchable(),
            Column::make('Cliente Final', 'cliente_final_nome')->searchable(),
            Column::make('Orçamentista', 'orcamentista.nome')->sortable()->searchable(),
            // Column::make('Qtd. Pico', 'quantitativo_pico')->sortable()->searchable(),
            // Column::make('Regime', 'regime_trabalho_label')->sortable()->searchable(),
            // Column::make('ICMS', 'icms_percent_fmt')->sortable(),
            // Column::make('Conf. Comercial', 'conferente_comercial_nome')->sortable()->searchable(),
            // Column::make('Conf. Orçamentista', 'conferente_orcamentista_nome')->sortable()->searchable(),
            // Column::make('Características Especiais', 'caracteristicas_especiais')->searchable(),
            Column::action('Ações'),
        ];
    }

    public function header(): array
    {
        // Sem "Novo" — requisito nasce junto com triagem via Observer.
        return [];
    }

    public function actions($row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-primary')
                ->openModal('modal.requisitos.edit', ['requisitoId' => $row->id]),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('triagem.numero_orcamento')
                ->placeholder('N.Orc.')
                ->filterRelation('triagem', 'numero_orcamento'),

            Filter::inputText('triagem.descricao_servico')
                ->placeholder('Descrição Serviço')
                ->filterRelation('triagem', 'descricao_servico'),

            Filter::inputText('cliente_final_nome')
                ->placeholder('Cliente Final')
                ->builder(function (Builder $query, $data) {
                    $value = is_array($data) ? (string)($data['value'] ?? '') : (string)$data;
                    $value = trim($value);
                    if ($value === '') return $query;

                    return $query->whereHas('triagem.clienteFinal', function ($q) use ($value) {
                        $q->where('nome_fantasia', 'like', "%{$value}%")
                        ->orWhere('nome_cliente', 'like', "%{$value}%");
                    });
                }),

            Filter::inputText('orcamentista.nome')
                ->placeholder('Orçamentista')
                ->filterRelation('orcamentista', 'nome'),
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
