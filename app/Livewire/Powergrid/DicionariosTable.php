<?php

namespace App\Livewire\Powergrid;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class DicionariosTable extends PowerGridComponent
{
    public string $tableName = 'dicionarios-table';

    protected $listeners = [
        'reloadPowergrid',
    ];

    public function reloadPowergrid(): void
    {
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
        $this->dispatch('$refresh');
    }

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Collection
    {
        return $this->dictUnion()
            ->get()
            ->map(function ($row) {
                $row->id = $row->dict_key . ':' . $row->dict_item_id;
                return $row;
            });
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
            Filter::select('dict_label', 'dict_key')
                ->dataSource($this->dictFilterOptions())
                ->optionLabel('name')
                ->optionValue('id'),
            Filter::inputText('Termo')
                ->placeholder('Termo'),
            Filter::inputText('Descricao_Padrao')
                ->placeholder('Descricao'),
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('dict_key')
            ->add('dict_label')
            ->add('dict_item_id')
            ->add('Termo')
            ->add('Descricao_Padrao')
            ->add('Revisao');
    }

    public function columns(): array
    {
        return [
            Column::make('Dicionario', 'dict_label')->searchable()->sortable(),
            Column::make('Termo', 'Termo')->searchable(),
            Column::make('Descricao_Padrao', 'Descricao_Padrao')->searchable(),
            Column::make('Revisao', 'Revisao')->sortable(),
            Column::action('Acoes'),
        ];
    }

    public function header(): array
    {
        return [
            Button::add('novo_item')
                ->slot('Novo item')
                ->class('btn btn-primary')
                ->openModal('modal.dicionarios.create-edit', []),
        ];
    }

    public function actions($row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.dicionarios.create-edit', [
                    'dictKey' => $row->dict_key,
                    'dictItemId' => (int) $row->dict_item_id,
                ]),
            Button::add('delete')
                ->slot('Excluir')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.dicionarios.confirm-delete', [
                    'dictKey' => $row->dict_key,
                    'dictItemId' => (int) $row->dict_item_id,
                ]),
        ];
    }

    private function dictFilterOptions(): array
    {
        $options = [];
        foreach ($this->dictMap() as $key => $meta) {
            $options[] = ['id' => $key, 'name' => $meta['label']];
        }

        return $options;
    }

    private function dictUnion(): QueryBuilder
    {
        $queries = [];

        foreach ($this->dictMap() as $key => $meta) {
            $table = $meta['table'];
            $label = $meta['label'];

            $queries[] = DB::table($table)->selectRaw(
                "'{$key}' as dict_key,
                 '{$label}' as dict_label,
                 id as dict_item_id,
                 Termo,
                 Descricao_Padrao,
                 Revisao"
            );
        }

        $union = array_shift($queries);

        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return $union;
    }

    private function dictMap(): array
    {
        return [
            'dict_ele_tipo' => [
                'label' => 'ELE - Tipo',
                'table' => 'dict_ele_tipo',
            ],
            'dict_ele_material' => [
                'label' => 'ELE - Material',
                'table' => 'dict_ele_material',
            ],
            'dict_ele_conexao' => [
                'label' => 'ELE - Conexao',
                'table' => 'dict_ele_conexao',
            ],
            'dict_ele_espessura' => [
                'label' => 'ELE - Espessura',
                'table' => 'dict_ele_espessura',
            ],
            'dict_ele_extremidade' => [
                'label' => 'ELE - Extremidade',
                'table' => 'dict_ele_extremidade',
            ],
            'dict_ele_dimensao' => [
                'label' => 'ELE - Dimensao',
                'table' => 'dict_ele_dimensao',
            ],
            'dict_tub_tipo' => [
                'label' => 'TUB - Tipo',
                'table' => 'dict_tub_tipo',
            ],
            'dict_tub_material' => [
                'label' => 'TUB - Material',
                'table' => 'dict_tub_material',
            ],
            'dict_tub_schedule' => [
                'label' => 'TUB - Schedule',
                'table' => 'dict_tub_schedule',
            ],
            'dict_tub_extremidade' => [
                'label' => 'TUB - Extremidade',
                'table' => 'dict_tub_extremidade',
            ],
            'dict_tub_diametro' => [
                'label' => 'TUB - Diametro',
                'table' => 'dict_tub_diametro',
            ],
        ];
    }
}
