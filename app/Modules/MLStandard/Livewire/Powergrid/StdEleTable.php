<?php

namespace App\Modules\MLStandard\Livewire\Powergrid;

use App\Modules\MLStandard\Models\StdELE;
use App\Modules\MLStandard\Models\StdEleTipo;
use App\Modules\MLStandard\Models\StdEleMaterial;
use App\Modules\MLStandard\Models\StdEleConexao;
use App\Modules\MLStandard\Models\StdEleEspessura;
use App\Modules\MLStandard\Models\StdEleExtremidade;
use App\Modules\MLStandard\Models\StdEleDimensao;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class StdEleTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
        'exportStdEle' => 'exportStdEle',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    public string $tableName = 'std-ele-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return StdELE::query()
            ->with(['tipo', 'material', 'conexao', 'espessura', 'extremidade', 'dimensao'])
            ->orderBy('id'); // mais novo primeiro
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('tipo_nome', fn (StdELE $row) => $row->tipo?->nome ?? '-')
            ->add('material_nome', fn (StdELE $row) => $row->material?->nome ?? '-')
            ->add('conexao_nome', fn (StdELE $row) => $row->conexao?->nome ?? '-')
            ->add('espessura_nome', fn (StdELE $row) => $row->espessura?->nome ?? '-')
            ->add('extremidade_nome', fn (StdELE $row) => $row->extremidade?->nome ?? '-')
            ->add('dimensao_nome', fn (StdELE $row) => $row->dimensao?->nome ?? '-')
            ->add('std')
            ->add('std_fmt', fn($row) => number_format((float) $row->std, 2, ',', '.'));            
    }

    public function columns(): array
    {
        return [
            Column::make('Tipo', 'tipo_nome')->searchable(),
            Column::make('Material', 'material_nome')->searchable(),
            Column::make('Conexão', 'conexao_nome')->searchable(),
            Column::make('Espessura', 'espessura_nome')->searchable(),
            Column::make('Extremidade', 'extremidade_nome')->searchable(),
            Column::make('Dimensão', 'dimensao_nome')->searchable(),
            Column::make('STD', 'std_fmt', 'std')->sortable(),
            Column::action('Ações')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('tipo_nome')
                ->placeholder('Tipo')
                ->filterRelation('tipo', 'nome'),

            Filter::inputText('material_nome')
                ->placeholder('Material')
                ->filterRelation('material', 'nome'),

            Filter::inputText('conexao_nome')
                ->placeholder('Conexão')
                ->filterRelation('conexao', 'nome'),

            Filter::inputText('espessura_nome')
                ->placeholder('Espessura')
                ->filterRelation('espessura', 'nome'),

            Filter::inputText('extremidade_nome')
                ->placeholder('Extremidade')
                ->filterRelation('extremidade', 'nome'),

            Filter::inputText('dimensao_nome')
                ->placeholder('Dimensão')
                ->filterRelation('dimensao', 'nome'),
        ];
    }

    /** Botão no header (CREATE) */
    public function header(): array
    {
        return [
            Button::add('novo')
                ->slot('Novo Std ELE')
                ->class('btn btn-primary')
                ->openModal('modal.std-ele.create-edit', []), // CREATE sem id

            Button::add('export')
                ->slot('Exportar Excel')
                ->class('btn btn-success')
                ->dispatch('exportStdEle', []),

            Button::add('import')
                ->slot('Importar Excel')
                ->class('btn btn-secondary')
                ->openModal('modal.std-ele.import', []),
        ];
    }

    // #[\Livewire\Attributes\On('edit')]
    // public function edit($rowId): void
    // {
    //     $this->js('alert('.$rowId.')');
    // }

    public function actions(StdELE $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.std-ele.create-edit', ['stdELEId' => $row->id]),

            Button::add('delete')
                ->slot('Deletar')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.std-ele.confirm-delete', ['stdELEId' => $row->id]),
        ];
    }

    public function exportStdEle()
    {
        $rows = StdELE::query()
            ->with(['tipo', 'material', 'conexao', 'espessura', 'extremidade', 'dimensao'])
            ->orderBy('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('StdELE');

        $headers = [
            'TIPO', 'MATERIAL', 'CONEXÃO', 'ESPESSURA', 'EXTREMIDADE', 'DIMENSÃO',
            'STD',

            // percentuais
            'ENCARREGADO MECÂNICA','ENCARREGADO TUBULAÇÃO','ENCARREGADO ELÉTRICA','ENCARREGADO ANDAIME','ENCARREGADO CIVIL',
            'LÍDER',
            'MECÂNICO AJUSTADOR','MECÂNICO MONTADOR','ENCANADOR','CALDEIREIRO','LIXADOR','MONTADOR',
            'SOLDADOR ER','SOLDADOR TIG','SOLDADOR MIG',
            'PONTEADOR',
            'ELETRICISTA CONTROLISTA','ELETRICISTA MONTADOR','INSTRUMENTISTA',
            'MONTADOR DE ANDAIME','PINTOR','JATISTA','PEDREIRO','CARPINTEIRO','ARMADOR','AJUDANTE'
        ];

        // header row
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col++, 1, $h);
        }

        // data
        $r = 2;
        foreach ($rows as $row) {

            $values = [
                $row->tipo?->nome ?? '',
                $row->material?->nome ?? '',
                $row->conexao?->nome ?? '',
                $row->espessura?->nome ?? '',
                $row->extremidade?->nome ?? '',
                $row->dimensao?->nome ?? '',
                (float) $row->std,

                (float) $row->encarregado_mecanica,
                (float) $row->encarregado_tubulacao,
                (float) $row->encarregado_eletrica,
                (float) $row->encarregado_andaime,
                (float) $row->encarregado_civil,
                (float) $row->lider,

                (float) $row->mecanico_ajustador,
                (float) $row->mecanico_montador,
                (float) $row->encanador,
                (float) $row->caldeireiro,
                (float) $row->lixador,
                (float) $row->montador,

                (float) $row->soldador_er,
                (float) $row->soldador_tig,
                (float) $row->soldador_mig,

                (float) $row->ponteador,

                (float) $row->eletricista_controlista,
                (float) $row->eletricista_montador,
                (float) $row->instrumentista,

                (float) $row->montador_de_andaime,
                (float) $row->pintor,
                (float) $row->jatista,
                (float) $row->pedreiro,
                (float) $row->carpinteiro,
                (float) $row->armador,
                (float) $row->ajudante,
            ];

            $c = 1;
            foreach ($values as $v) {
                $sheet->setCellValueByColumnAndRow($c++, $r, $v);
            }

            $r++;
        }

        // salva em arquivo temporário
        $filename = 'StdELE_' . now()->format('Ymd_His') . '.xlsx';
        $path = storage_path("app/tmp/{$filename}");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
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