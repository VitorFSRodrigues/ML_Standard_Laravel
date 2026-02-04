<?php

namespace App\Livewire\Powergrid;

use App\Models\TreinoLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class TreinoLogsTable extends PowerGridComponent
{
    public string $tableName = 'treino-logs-table';
    private ?array $jobFirstAt = null;

    protected $listeners = [
        'reloadPowergrid',
    ];

    public function reloadPowergrid(): void
    {
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
        $this->refresh();
        $this->dispatch('$refresh');
    }

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return TreinoLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('job_id')->placeholder('Job ID'),
            Filter::select('status')
                ->dataSource([
                    ['id' => 'queued', 'name' => 'queued'],
                    ['id' => 'running', 'name' => 'running'],
                    ['id' => 'completed', 'name' => 'completed'],
                    ['id' => 'failed', 'name' => 'failed'],
                    ['id' => 'error', 'name' => 'error'],
                ])
                ->optionLabel('name')
                ->optionValue('id'),
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('created_at_fmt', fn (TreinoLog $row) => Carbon::parse($row->created_at)->format('d/m/Y H:i:s'))
            ->add('job_id')
            ->add('varredura_id')
            ->add('elapsed_fmt', function (TreinoLog $row) {
                $map = $this->jobFirstAtMap();
                $first = $map[$row->job_id] ?? null;
                if (!$first) {
                    return '-';
                }

                $start = Carbon::parse($first);
                $current = Carbon::parse($row->created_at);
                $seconds = $start->diffInSeconds($current);

                if ($seconds < 60) {
                    return $seconds . 's';
                }

                $minutes = intdiv($seconds, 60);
                $rem = $seconds % 60;

                if ($minutes < 60) {
                    return sprintf('%dm %02ds', $minutes, $rem);
                }

                $hours = intdiv($minutes, 60);
                $min = $minutes % 60;

                return sprintf('%dh %02dm %02ds', $hours, $min, $rem);
            })
            ->add('status_badge', function (TreinoLog $row) {
                $status = strtolower((string) ($row->status ?? ''));
                $map = [
                    'queued' => ['badge badge-warning', 'EM FILA'],
                    'running' => ['badge badge-info', 'TREINANDO'],
                    'completed' => ['badge badge-success', 'CONCLUIDO'],
                    'failed' => ['badge badge-danger', 'FALHOU'],
                    'error' => ['badge badge-danger', 'ERRO'],
                ];

                [$cls, $label] = $map[$status] ?? ['badge badge-secondary', strtoupper($status ?: '-')];
                return new HtmlString("<span class=\"{$cls}\">{$label}</span>");
            })
            ->add('message_short', function (TreinoLog $row) {
                if (!$row->message) {
                    return '-';
                }

                return new HtmlString('<span title="' . e($row->message) . '">' . e(Str::limit($row->message, 80, '...')) . '</span>');
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Data/Hora', 'created_at_fmt', 'created_at')->sortable(),
            Column::make('Job ID', 'job_id')->searchable(),
            Column::make('Varredura', 'varredura_id')->sortable(),
            Column::make('Tempo decorrido', 'elapsed_fmt'),
            Column::make('Status', 'status_badge'),
            Column::make('Mensagem', 'message_short')->searchable(),
        ];
    }

    private function jobFirstAtMap(): array
    {
        if ($this->jobFirstAt !== null) {
            return $this->jobFirstAt;
        }

        $this->jobFirstAt = TreinoLog::query()
            ->select('job_id', DB::raw('MIN(created_at) as first_at'))
            ->groupBy('job_id')
            ->pluck('first_at', 'job_id')
            ->toArray();

        return $this->jobFirstAt;
    }
}
