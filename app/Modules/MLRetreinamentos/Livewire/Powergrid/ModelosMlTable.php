<?php

namespace App\Modules\MLRetreinamentos\Livewire\Powergrid;

use App\Modules\MLRetreinamentos\Models\ModeloMl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class ModelosMlTable extends PowerGridComponent
{
    public string $tableName = 'modelos-ml-table';

    protected $listeners = [
        'reloadPowergrid',
        'setCurrentRevisionML' => 'setCurrentRevision',
    ];

    public function reloadPowergrid(): void
    {
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
        $this->dispatch('$refresh');
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return ModeloMl::query()
            ->orderByDesc('is_current')
            ->orderByDesc('data')
            ->orderByDesc('revisao');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('disciplina')
            ->add('data_fmt', fn (ModeloMl $row) => Carbon::parse($row->data)->format('d/m/Y'))
            ->add('revisao')
            ->add('acuracia_fmt', function (ModeloMl $row) {
                if ($row->acuracia === null) {
                    return '-';
                }

                return number_format($row->acuracia, 4, ',', '.');
            })
            ->add('treino_exact_match_fmt', function (ModeloMl $row) {
                if ($row->treino_exact_match_ratio === null) {
                    return '-';
                }

                return number_format($row->treino_exact_match_ratio, 4, ',', '.');
            })
            ->add('treino_amostras_fmt', function (ModeloMl $row) {
                if ($row->treino_n_samples === null && $row->treino_n_train === null && $row->treino_n_test === null) {
                    return '-';
                }

                $total = $row->treino_n_samples ?? '-';
                $train = $row->treino_n_train ?? '-';
                $test = $row->treino_n_test ?? '-';

                return "{$total} (treino {$train} / teste {$test})";
            })
            ->add('treino_periodo_fmt', function (ModeloMl $row) {
                $start = $row->treino_started_at ? Carbon::parse($row->treino_started_at)->format('d/m/Y H:i') : '-';
                $end = $row->treino_finished_at ? Carbon::parse($row->treino_finished_at)->format('d/m/Y H:i') : '-';

                if ($start === '-' && $end === '-') {
                    return '-';
                }

                return "{$start} → {$end}";
            })
            ->add('treino_status_badge', function (ModeloMl $row) {
                $status = strtolower((string) ($row->treino_status ?? ''));
                $map = [
                    'queued' => ['badge badge-warning', 'EM FILA'],
                    'running' => ['badge badge-info', 'TREINANDO'],
                    'completed' => ['badge badge-success', 'CONCLUIDO'],
                    'failed' => ['badge badge-danger', 'FALHOU'],
                ];

                if ($status === '') {
                    return '-';
                }

                [$cls, $label] = $map[$status] ?? ['badge badge-secondary', strtoupper($status)];
                return new HtmlString("<span class=\"{$cls}\">{$label}</span>");
            })
            ->add('treino_error_short', function (ModeloMl $row) {
                if (!$row->treino_error) {
                    return '-';
                }

                return new HtmlString('<span title="' . e($row->treino_error) . '">' . e(Str::limit($row->treino_error, 60, '...')) . '</span>');
            })
            ->add('is_current_badge', function (ModeloMl $row) {
                if (!$row->is_current) {
                    return '';
                }

                return new HtmlString('<span class="badge badge-success">ATUAL</span>');
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Disciplina', 'disciplina')->sortable()->searchable(),
            Column::make('Data', 'data_fmt', 'data')->sortable(),
            Column::make('Revisão', 'revisao')->sortable(),
            Column::make('Acurácia', 'acuracia_fmt', 'acuracia')->sortable(),
            Column::make('Exact Match', 'treino_exact_match_fmt'),
            Column::make('Amostras', 'treino_amostras_fmt'),
            Column::make('Período Treino', 'treino_periodo_fmt'),
            Column::make('Status Treino', 'treino_status_badge'),
            Column::make('Erro Treino', 'treino_error_short'),
            Column::make('Atual', 'is_current_badge'),
        ];
    }

    public function header(): array
    {
        return [
            Button::add('set_current')
                ->slot('Usar Revisão Selecionada')
                ->class('btn btn-sm btn-success')
                ->dispatch('setCurrentRevisionML', []),
        ];
    }

    public function setCurrentRevision(): void
    {
        $ids = array_map('intval', $this->checkedValues());

        if (empty($ids)) {
            return;
        }

        $rows = ModeloMl::query()
            ->whereIn('id', $ids)
            ->get(['id', 'disciplina']);

        if ($rows->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                ModeloMl::query()
                    ->where('disciplina', $row->disciplina)
                    ->update(['is_current' => false]);

                ModeloMl::query()
                    ->where('id', $row->id)
                    ->update(['is_current' => true]);
            }
        });

        $this->checkboxValues = [];
        $this->dispatch('pgBulkActions::clear', $this->tableName);
        $this->dispatch('reloadPowergrid');
    }
}
