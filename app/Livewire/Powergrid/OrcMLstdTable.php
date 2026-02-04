<?php

namespace App\Livewire\Powergrid;

use App\Models\OrcMLstd;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class OrcMLstdTable extends PowerGridComponent
{
    public string $tableName = 'orc-mlstd-table';

    protected $listeners = [
            'reloadPowergrid',
        ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),

            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        $query = OrcMLstd::query()
            ->with('orcamentista');

        $seedStartId = OrcMLstd::query()
            ->where('numero_orcamento', '0001')
            ->where('rev', 0)
            ->value('id');

        if ($seedStartId !== null) {
            $query->where('id', '>=', $seedStartId);
        }

        return $query->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('numero_orcamento')
            ->add('rev')
            ->add('orcamentista_nome', fn (OrcMLstd $row) => $row->orcamentista?->nome ?? '-')
            ->add('created_at_formatted', fn (OrcMLstd $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Numero orcamento', 'numero_orcamento')
                ->sortable()
                ->searchable(),

            Column::make('Rev', 'rev'),
            Column::make('Orçamentista', 'orcamentista_nome')
                ->sortable()
                ->searchable(),

            // Column::make('Created at', 'created_at_formatted', 'created_at')
            //     ->sortable(),

            Column::action('Ações')
        ];
    }

    public function header(): array
    {
        return [
            Button::add('novo')
                ->slot('Novo Orçamento')
                ->class('btn btn-primary')
                ->openModal('modal.orcmlstd.create-edit', []),
        ];
    }

    public function actions($row): array
    {
        return [
            Button::add('levantamento')
                ->slot('Levantar Horas')
                ->class('btn btn-xs btn-info')
                ->route('orc-mlstd.levantamento', ['id' => $row->id]),

            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.orcmlstd.create-edit', ['orcMLstdId' => $row->id]),

            Button::add('delete')
                ->slot('Deletar')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.orcmlstd.confirm-delete', ['orcMLstdId' => $row->id]),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datetimepicker('created_at'),
        ];
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
