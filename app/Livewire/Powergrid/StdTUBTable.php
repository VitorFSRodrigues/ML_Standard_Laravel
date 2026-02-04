<?php

namespace App\Livewire\Powergrid;

use App\Models\StdTUB;
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

final class StdTUBTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
        'exportStdTub' => 'exportStdTub',
    ];

    public function reloadPowergrid()
    {
        $this->refresh();
    }

    public string $tableName = 'std-tub-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return StdTUB::query()
            ->with(['tipo', 'material', 'schedule', 'extremidade', 'diametro'])
            ->orderBy('id'); // ✅ mais novo primeiro
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('tipo_nome', fn (StdTUB $row) => $row->tipo?->nome ?? '-')
            ->add('material_nome', fn (StdTUB $row) => $row->material?->nome ?? '-')
            ->add('schedule_nome', fn (StdTUB $row) => $row->schedule?->nome ?? '-')
            ->add('extremidade_nome', fn (StdTUB $row) => $row->extremidade?->nome ?? '-')
            ->add('diametro_nome', fn (StdTUB $row) => $row->diametro?->nome ?? '-')
            ->add('hh_un')
            ->add('hh_un_fmt', fn($row) => number_format((float) $row->hh_un, 2, ',', '.'))

            ->add('kg_hh')
            ->add('kg_hh_fmt', fn($row) => number_format((float) $row->kg_hh, 2, ',', '.'))

            ->add('kg_un')
            ->add('kg_un_fmt', fn($row) => number_format((float) $row->kg_un, 2, ',', '.'))

            ->add('m2_un')
            ->add('m2_un_fmt', fn($row) => number_format((float) $row->m2_un, 2, ',', '.'));
    }

    public function columns(): array
    {
        return [
            Column::make('Tipo', 'tipo_nome')->searchable(),
            Column::make('Material', 'material_nome')->searchable(),
            Column::make('Schedule', 'schedule_nome')->searchable(),
            Column::make('Extremidade', 'extremidade_nome')->searchable(),
            Column::make('Diâmetro', 'diametro_nome')->searchable(),
            Column::make('HH/UN', 'hh_un_fmt', 'hh_un')->sortable(),
            Column::make('KG/HH', 'kg_hh_fmt', 'kg_hh')->sortable(),
            Column::make('KG/UN', 'kg_un_fmt', 'kg_un')->sortable(),
            Column::make('M2/UN', 'm2_un_fmt', 'm2_un')->sortable(),
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

            Filter::inputText('schedule_nome')
                ->placeholder('Schedule')
                ->filterRelation('schedule', 'nome'),

            Filter::inputText('extremidade_nome')
                ->placeholder('Extremidade')
                ->filterRelation('extremidade', 'nome'),

            Filter::inputText('diametro_nome')
                ->placeholder('Diâmetro')
                ->filterRelation('diametro', 'nome'),
        ];
    }

    /** Botão no header (CREATE) */
    public function header(): array
    {
        return [
            Button::add('novo')
                ->slot('Novo Std TUB')
                ->class('btn btn-primary')
                ->openModal('modal.std-tub.create-edit', []), // CREATE sem id
            
            Button::add('export')
                ->slot('Exportar Excel')
                ->class('btn btn-success')
                ->dispatch('exportStdTub', []),

            Button::add('import')
                ->slot('Importar Excel')
                ->class('btn btn-secondary')
                ->openModal('modal.std-tub.import', []),    
        ];
    }

    // #[\Livewire\Attributes\On('edit')]
    // public function edit($rowId): void
    // {
    //     $this->js('alert('.$rowId.')');
    // }

    public function actions(StdTUB $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.std-tub.create-edit', ['stdTUBId' => $row->id]),

            Button::add('delete')
                ->slot('Deletar')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.std-tub.confirm-delete', ['stdTUBId' => $row->id]),
        ];
    }

    public function exportStdTub()
    {
        $rows = StdTUB::query()
            ->with(['tipo', 'material', 'schedule', 'extremidade', 'diametro'])
            ->orderBy('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('StdTUB');

        $headers = [
            'TIPO', 'MATERIAL', 'SCHEDULE', 'EXTREMIDADE', 'DIÂMETRO',
            'HH/UN', 'KG/HH', 'KG/UN', 'M2/UN',

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
                $row->schedule?->nome ?? '',
                $row->extremidade?->nome ?? '',
                $row->diametro?->nome ?? '',

                (float) $row->hh_un,
                (float) $row->kg_hh,
                (float) $row->kg_un,
                (float) $row->m2_un,

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

        $filename = 'StdTUB_' . now()->format('Ymd_His') . '.xlsx';
        $path = storage_path("app/tmp/{$filename}");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

}
