<?php

namespace App\Modules\MLStandard\Livewire\Powergrid;

use App\Modules\MLStandard\Models\EvidenciaUsoMl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class EvidenciasUsoMlTable extends PowerGridComponent
{
    public string $tableName = 'evidencias-uso-ml-table';

    public float $tempoEleMin = 1.0;
    public float $tempoTubMin = 1.0;

    protected $listeners = [
        'reloadPowergrid',
    ];

    public function mount($tempoEleMin = 1.0, $tempoTubMin = 1.0): void
    {
        $this->tempoEleMin = max(0.01, (float) $tempoEleMin);
        $this->tempoTubMin = max(0.01, (float) $tempoTubMin);

        parent::mount();
    }

    public function reloadPowergrid(): void
    {
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
        $this->dispatch('$refresh');
    }

    public function setUp(): array
    {
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
        $statsByOrc = DB::table('orc_ml_std_itens as i')
            ->select([
                'i.orc_ml_std_id as orc_ml_std_id',
                DB::raw('MAX(i.updated_at) as data_modificacao'),
            ])
            ->groupBy('i.orc_ml_std_id');

        return EvidenciaUsoMl::query()
            ->from('evidencias_uso_ml as ev')
            ->join('orc_ml_std as o', 'o.id', '=', 'ev.orc_ml_std_id')
            ->leftJoin('orcamentistas as orc', 'orc.id', '=', 'o.orcamentista_id')
            ->leftJoinSub($statsByOrc, 'st', function ($join) {
                $join->on('st.orc_ml_std_id', '=', 'ev.orc_ml_std_id');
            })
            ->select([
                'ev.id as id',
                'ev.orc_ml_std_id as orc_ml_std_id',
                'o.numero_orcamento as numero_orcamento',
                'o.rev as revisao',
                'orc.nome as orcamentista_nome',
                'o.created_at as orc_ml_std_created_at',
                'ev.qtd_itens_ele as qtd_itens_ele',
                'ev.qtd_itens_tub as qtd_itens_tub',
                'ev.data_modificacao as data_modificacao',
                'ev.tempo_normal_hr as tempo_normal_hr',
                'ev.tempo_ml_hr as tempo_ml_hr',
            ])
            ->orderByDesc('ev.id')
            ->orderByRaw('COALESCE(ev.data_modificacao, ev.updated_at) DESC');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('orc_ml_std_id')
            ->add('data_modificacao_fmt', function ($row): string {
                if (empty($row->data_modificacao)) {
                    return '-';
                }

                return Carbon::parse($row->data_modificacao)->format('d/m/Y H:i:s');
            })
            ->add('numero_orcamento')
            ->add('revisao', fn ($row) => (int) $row->revisao)
            ->add('orcamentista_nome')
            ->add('qtd_itens_ele', fn ($row) => (int) ($row->qtd_itens_ele ?? 0))
            ->add('qtd_itens_tub', fn ($row) => (int) ($row->qtd_itens_tub ?? 0))
            ->add('tempo_normal_hr_fmt', function ($row): string {
                if ($row->tempo_normal_hr === null || $row->tempo_normal_hr === '') {
                    return '-';
                }

                return number_format((float) $row->tempo_normal_hr, 2, ',', '.');
            })
            ->add('tempo_ml_hr_fmt', function ($row): string {
                if ($row->tempo_ml_hr === null || $row->tempo_ml_hr === '') {
                    return '-';
                }

                return number_format((float) $row->tempo_ml_hr, 2, ',', '.');
            })
            ->add('data_modificacao', fn ($row) => $row->data_modificacao);
    }

    public function columns(): array
    {
        return [
            Column::make('Data', 'data_modificacao_fmt', 'data_modificacao')->sortable(),
            Column::make('Numero do Orçamento', 'numero_orcamento')->sortable()->searchable(),
            Column::make('Revisão', 'revisao')->sortable(),
            Column::make('Orcamentista', 'orcamentista_nome')->sortable()->searchable(),
            Column::make('Qtd Itens ELE', 'qtd_itens_ele')->sortable(),
            Column::make('Qtd Itens TUB', 'qtd_itens_tub')->sortable(),
            Column::make('Tempo Normal (hr)', 'tempo_normal_hr_fmt'),
            Column::make('Tempo ML (hr)', 'tempo_ml_hr_fmt'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datetimepicker('data_modificacao', 'ev.data_modificacao'),
            Filter::inputText('numero_orcamento', 'o.numero_orcamento')->placeholder('Numero do orcamento'),
            Filter::inputText('orcamentista_nome', 'orc.nome')->placeholder('Orcamentista'),
        ];
    }
}


