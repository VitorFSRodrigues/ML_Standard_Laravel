<?php

namespace App\Livewire\Powergrid;

use App\Models\Pergunta;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use App\Helpers\PowerGridThemes\TailwindHeaderFixed;

final class PerguntaTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
    ];

    public function customThemeClass(): ?string
    {
        return TailwindHeaderFixed::class;
    }

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    public string $tableName = 'perguntas-table';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            // PowerGrid::footer()
            //     ->showPerPage()
            //     ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Pergunta::query()->orderBy('id', 'asc');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('descricao')
            ->add('peso')
            
            // label amigável para o boolean (evita problemas de render)
            ->add('padrao_label', fn (Pergunta $p) => $p->padrao ? 'Sim' : 'Não')

            ->add('created_at_formatted', fn (Pergunta $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            Column::make('Descrição', 'descricao')
                ->searchable()
                ->sortable(),

            Column::make('Peso', 'peso')
                ->sortable()
                ->searchable(),

            Column::make('Padrão', 'padrao_label')
                ->sortable()
                ->searchable(),

            Column::make('Created at', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Ações')
        ];
    }

    /** Botão no header (CREATE) */
    public function header(): array
    {
        return [
            Button::add('novo')
                ->slot('Nova Pergunta')
                ->class('btn btn-primary')
                ->openModal('modal.perguntas.create-edit', []), // CREATE sem id
        ];
    }

    /** Ações por linha (EDIT/DELETE) */
    public function actions($row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.perguntas.create-edit', ['perguntaId' => $row->id]),

            Button::add('delete')
                ->slot('Deletar')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.perguntas.confirm-delete', ['perguntaId' => $row->id]),
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
