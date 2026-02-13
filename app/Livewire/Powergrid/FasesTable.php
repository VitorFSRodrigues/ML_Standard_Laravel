<?php

namespace App\Livewire\Powergrid;

use App\Models\Fase;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class FasesTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    public string $tableName = 'fases-table';
    public int|string $triagemId; // recebido pela Blade

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
        return Fase::query()
            ->where('triagem_id', (int)$this->triagemId)
            ->with([
                'triagem:id,numero_orcamento,descricao_resumida,caracteristica_orcamento,cliente_id',
                'triagem.cliente:id,nome_fantasia,nome_cliente',
            ])
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
            ->add('revisao')
            ->add('versao')
            ->add('comentario')
            ->add('created_at')
            ->add('updated_at')
            // 🔽 Nome Fase
            ->add('nome_fase', function ($row) {
                $orc     = trim((string) ($row->triagem->numero_orcamento ?? ''));
                $rev     = (int) ($row->revisao ?? 0);
                $ver     = (int) ($row->versao ?? 1);
                $cliente = trim(
                    $row->triagem?->cliente?->nome_fantasia
                    ?? $row->triagem?->cliente?->nome_cliente
                    ?? ''
                );
                $escopo  = trim((string) ($row->triagem->descricao_resumida ?? ''));

                // Resolve label da característica
                $label = $row->triagem->caracteristica_orcamento?->value
                    ?? (string) $row->triagem->caracteristica_orcamento
                    ?? (string) ($row->triagem->caracteristica_orcamento_label ?? '');

                // Mapa para sufixo (igual ao usado no nome_pasta)
                $suf = match ($label) {
                    'FAST'       => 'F',
                    'Fabricação' => 'E',
                    'Painéis'    => 'P',
                    'Engenharia' => 'Eng',
                    'Montagem'   => '',
                    default      => '',
                };

                // monta: fase {N}(SFX)-CLIENTE_ESC.Rev.X.Ver.Y
                $prefixo = 'fase ' . $orc;
                $carac   = $suf !== '' ? "{$suf}" : '';
                $base    = "{$prefixo}{$carac}-{$cliente}_{$escopo}";

                return "{$base}.Rev.{$rev}.Ver.{$ver}";
            });
    }

    public function columns(): array
    {
        return [
            // Column::make('ID', 'id')->sortable(),
            Column::make('Nome Fase', 'nome_fase')->searchable()->sortable(),
            Column::make('Revisão', 'revisao')->sortable()->searchable(),
            Column::make('Versão', 'versao')->sortable()->searchable(),
            Column::make('Observação', 'comentario')->sortable()->searchable(),
            Column::make('Criado em', 'created_at'),
            Column::make('Atualizado em', 'updated_at'),
            Column::action('Ações'),
        ];
    }

    /** Botão de criar (header) */
    public function header(): array
    {
        return [
            Button::add('novo')
                ->slot('Novo Fase')
                ->class('btn btn-primary')
                ->openModal('modal.fases.create-edit', [
                    'faseId'    => null,
                    'triagemId' => (int)$this->triagemId,
                ]),
        ];
    }

    /** Ações de linha */
    public function actions($row): array
    {
        return [
            Button::add('editar')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.fases.create-edit', [
                    'faseId'    => $row->id,
                    'triagemId' => (int)$this->triagemId,
                ]),

            Button::add('deletar')
                ->slot('Excluir')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.fases.confirm-delete', [
                    'faseId' => $row->id,
                ]),
        ];
    }

    public function filters(): array
    {
        return [
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
