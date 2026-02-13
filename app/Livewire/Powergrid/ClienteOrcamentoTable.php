<?php

namespace App\Livewire\Powergrid;

use App\Models\Cliente;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class ClienteOrcamentoTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    public string $tableName = 'clientes-orcamentos';

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
        return Cliente::query()->orderBy('id', 'asc');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('nome_cliente')
            ->add('nome_fantasia')
            ->add('endereco_completo')
            ->add('municipio')
            ->add('estado')
            ->add('pais')
            ->add('cnpj')
            ->add('created_at_formatted', fn (Cliente $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            // Column::make('Id', 'id'),
            Column::make('Nome cliente', 'nome_cliente')
                ->sortable()
                ->searchable(),
            Column::make('Nome Fantasia', 'nome_fantasia')->searchable()->sortable(),
            Column::make('Endereco completo', 'endereco_completo')
                ->sortable()
                ->searchable(),

            Column::make('Municipio', 'municipio')
                ->sortable()
                ->searchable(),

            Column::make('Estado', 'estado')
                ->sortable()
                ->searchable(),

            Column::make('Pais', 'pais')
                ->sortable()
                ->searchable(),

            Column::make('Cnpj', 'cnpj')
                ->sortable()
                ->searchable(),

            Column::make('Created at', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Ações')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('nome_cliente')->placeholder('Nome cliente'),
            Filter::inputText('nome_fantasia')->placeholder('Nome Fantasia'),
            Filter::inputText('endereco_completo')->placeholder('Endereço'),
            Filter::inputText('municipio')->placeholder('Município'),
            Filter::inputText('estado')->placeholder('Estado'),
            Filter::inputText('pais')->placeholder('País'),
            Filter::inputText('cnpj')->placeholder('CNPJ'),
            Filter::datetimepicker('created_at'),
        ];
    }


    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert('.$rowId.')');
    }

    public function header(): array
    {
        return [
            Button::add('novo')->slot('Novo cadastro')->class('btn btn-primary')
                ->openModal('modal.clientes.create-edit', []), // CREATE
        ];
    }

    public function actions(Cliente $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.clientes.create-edit', ['clienteId' => $row->id]),
            
            Button::add('delete')
                ->slot('Deletar')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.clientes.confirm-delete', ['clienteId' => $row->id]),
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
