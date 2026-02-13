<?php

namespace App\Livewire\Powergrid;

use App\Enums\DestinoTriagem;
use App\Models\{Orcamentista, Triagem};
use Illuminate\Support\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use Illuminate\Support\Facades\DB;

final class OrcamentistaTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    public string $tableName = 'orcamentistas-table';

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
        return Orcamentista::query()
            ->withCount([
                'triagens as orcamentos_count' => function ($q) {
                    $q->where('destino', DestinoTriagem::ORCAMENTO->value);
                    // (opcional) só contar ativos:
                    // $q->where('status', true);
                },
            ])
            ->addSelect([
                'orcamentos_lista' => Triagem::selectRaw("GROUP_CONCAT(numero_orcamento, ', ')")
                    ->whereColumn('triagem.orcamentista_id', 'orcamentistas.id')
                    ->where('destino', DestinoTriagem::ORCAMENTO->value)
                    // ->where('status', true)
            ])
            ->orderBy('nome');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('nome')
            ->add('email')
            ->add('created_at_formatted', fn (Orcamentista $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'))
            ->add('orcamentos_count', fn ($row) => (int) ($row->orcamentos_count ?? 0)) // garante 0
            ->add('orcamentos_lista', fn($r) => $r->orcamentos_lista ?? ''); // já vem concatenado
    }

    public function columns(): array
    {
        return [
            // Column::make('Id', 'id'),
            Column::make('Nome', 'nome')
                ->sortable()
                ->searchable(),
            Column::make('Orçamentos', 'orcamentos_count')->sortable(),
            Column::make('Nº Orçamentos', 'orcamentos_lista')->searchable(),
            // Column::make('Email', 'email')
            //     ->sortable()
            //     ->searchable(),
            // Column::make('Created at', 'created_at_formatted', 'created_at')
            //     ->sortable(),
            Column::action('Ações')
        ];
    }

    /** Botão "Novo" no header */
    public function header(): array
    {
        return [
            Button::add('novo')
                ->slot('Novo cadastro')
                ->class('btn btn-primary')
                ->openModal('modal.orcamentistas.create-edit', []),
        ];
    }

    /** Botões por linha */
    public function actions($row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.orcamentistas.create-edit', ['orcamentistaId' => $row->id]),

            Button::add('delete')
                ->slot('Deletar')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.orcamentistas.confirm-delete', ['orcamentistaId' => $row->id]),

            Button::add('view')
                ->slot('Visualizar')
                ->class('btn btn-xs btn-primary')
                ->route('orcamentista.show', ['orcamentista' => $row->id]),

        ];
    }

    public function filters(): array
    {
        return [
            Filter::datetimepicker('created_at'),
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
