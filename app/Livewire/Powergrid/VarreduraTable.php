<?php

namespace App\Livewire\Powergrid;

use App\Models\DictEleConexao;
use App\Models\DictEleDimensao;
use App\Models\DictEleEspessura;
use App\Models\DictEleExtremidade;
use App\Models\DictEleMaterial;
use App\Models\DictEleTipo;
use App\Models\DictTubDiametro;
use App\Models\DictTubExtremidade;
use App\Models\DictTubMaterial;
use App\Models\DictTubSchedule;
use App\Models\DictTubTipo;
use App\Models\MlFeedbackSample;
use App\Models\ModeloMl;
use App\Models\Varredura;
use App\Jobs\PollTrainStatusJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class VarreduraTable extends PowerGridComponent
{
    public string $tableName = 'varredura-table';

    protected $listeners = [
        'reloadPowergrid',
        'trainModelVarredura' => 'trainModel',
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

    public function datasource(): Builder
    {
        return Varredura::query()
            ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('revisao_ele')
            ->add('revisao_tub')
            ->add('status_ele_badge', function (Varredura $row) {
                $cls = $row->status_ele ? 'badge badge-success' : 'badge badge-secondary';
                $label = $row->status_ele ? 'PRONTO' : 'PENDENTE';
                return new HtmlString("<span class=\"{$cls}\">{$label}</span>");
            })
            ->add('status_tub_badge', function (Varredura $row) {
                $cls = $row->status_tub ? 'badge badge-success' : 'badge badge-secondary';
                $label = $row->status_tub ? 'PRONTO' : 'PENDENTE';
                return new HtmlString("<span class=\"{$cls}\">{$label}</span>");
            })
            ->add('treino_status_badge', function (Varredura $row) {
                $status = strtolower((string) ($row->treino_status ?? ''));
                $map = [
                    'queued' => ['badge badge-warning', 'EM FILA'],
                    'running' => ['badge badge-info', 'TREINANDO'],
                    'completed' => ['badge badge-success', 'CONCLUIDO'],
                    'failed' => ['badge badge-danger', 'FALHOU'],
                    'pendente' => ['badge badge-secondary', 'PENDENTE'],
                ];

                [$cls, $label] = $map[$status] ?? ['badge badge-secondary', strtoupper($status ?: 'PENDENTE')];
                return new HtmlString("<span class=\"{$cls}\">{$label}</span>");
            });
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make('Próx. Rev. ELE', 'revisao_ele')->sortable(),
            Column::make('Próx. Rev. TUB', 'revisao_tub')->sortable(),
            Column::make('Status ELE', 'status_ele_badge'),
            Column::make('Status TUB', 'status_tub_badge'),
            Column::make('Status Treino', 'treino_status_badge'),
            Column::action('Ações'),
        ];
    }

    public function header(): array
    {
        $buttons = [
            Button::add('novo')
                ->slot('Novo')
                ->class('btn btn-primary')
                ->openModal('modal.varredura.create-edit', []),
        ];

        return $buttons;
    }

    public function actions(Varredura $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-xs btn-warning')
                ->openModal('modal.varredura.create-edit', ['varreduraId' => $row->id]),
            Button::add('fazer_varredura')
                ->slot('Fazer Varredura')
                ->class('btn btn-xs btn-info')
                ->route('varredura.show', ['varredura' => $row->id]),
            Button::add('delete')
                ->slot('Excluir')
                ->class('btn btn-xs btn-danger')
                ->openModal('modal.varredura.confirm-delete', ['varreduraId' => $row->id]),
            Button::add('treinar_modelo')
                ->slot('Treinar modelo')
                ->class('btn btn-xs btn-success')
                ->dispatch('trainModelVarredura', []),
        ];
    }

    public function actionRules($row): array
    {
        $latest = Varredura::query()->orderByDesc('id')->first();
        $canTrain = $latest
            && (int) $row->id === (int) $latest->id
            && $latest->status_ele
            && $latest->status_tub;

        $isCompletedRow = (bool) $row->status_ele
            && (bool) $row->status_tub
            && strtolower((string) ($row->treino_status ?? '')) === 'completed';

        return [
            \PowerComponents\LivewirePowerGrid\Facades\Rule::button('treinar_modelo')
                ->when(fn () => ! $canTrain)
                ->hide(),
            \PowerComponents\LivewirePowerGrid\Facades\Rule::button('edit')
                ->when(fn () => $isCompletedRow)
                ->hide(),
            \PowerComponents\LivewirePowerGrid\Facades\Rule::button('fazer_varredura')
                ->when(fn () => $isCompletedRow)
                ->hide(),
            \PowerComponents\LivewirePowerGrid\Facades\Rule::button('delete')
                ->when(fn () => $isCompletedRow)
                ->hide(),
            \PowerComponents\LivewirePowerGrid\Facades\Rule::button('treinar_modelo')
                ->when(fn () => $isCompletedRow)
                ->hide(),
        ];
    }

    public function trainModel(): void
    {
        $current = Varredura::query()->orderByDesc('id')->first();
        if (!$current || !$current->status_ele || !$current->status_tub) {
            return;
        }

        $timestamp = Carbon::now()->format('Ymd_His');

        $dadosDir = 'C:\\ProjetoTelaML\\ml_api\\app\\resources\\dados_retreino';
        File::ensureDirectoryExists($dadosDir);
        $dadosPath = $dadosDir . "\\dados_treino_{$timestamp}.csv";

        $this->exportMlFeedbackSamples($dadosPath);

        $dictEleDir = 'C:\\ProjetoTelaML\\ml_api\\app\\resources\\ele\\dicionarios\\Rev.' . (int) $current->revisao_ele;
        $dictTubDir = 'C:\\ProjetoTelaML\\ml_api\\app\\resources\\tub\\dicionarios\\Rev.' . (int) $current->revisao_tub;
        File::ensureDirectoryExists($dictEleDir);
        File::ensureDirectoryExists($dictTubDir);

        $dictElePaths = $this->exportDictsEle($dictEleDir);
        $dictTubPaths = $this->exportDictsTub($dictTubDir);

        $baseUrl = config('services.ml_api.url', 'http://127.0.0.1:8001');
        try {
            $response = Http::timeout(120)->post($baseUrl . '/train', [
                'revisao_ele' => (int) $current->revisao_ele,
                'revisao_tub' => (int) $current->revisao_tub,
                'dados_treino_path' => $dadosPath,
                'dict_ele_paths' => $dictElePaths,
                'dict_tub_paths' => $dictTubPaths,
            ]);

            if ($response->ok()) {
                $payload = $response->json() ?? [];
                $jobId = (string) ($payload['job_id'] ?? '');
                $status = (string) ($payload['status'] ?? 'running');
                $status = $status !== '' ? $status : 'running';

                $current->update([
                    'treino_status' => $status,
                ]);

                $this->upsertModeloMl((int) $current->revisao_ele, 'ELE', $jobId, $payload);
                $this->upsertModeloMl((int) $current->revisao_tub, 'TUB', $jobId, $payload);

                if ($jobId !== '') {
                    PollTrainStatusJob::dispatch(
                        $jobId,
                        (int) $current->id,
                        (int) $current->revisao_ele,
                        (int) $current->revisao_tub
                    )->delay(now()->addSeconds(10));
                }
            } else {
                $current->update(['treino_status' => 'failed']);
            }
        } catch (\Throwable $e) {
            $current->update(['treino_status' => 'failed']);
            report($e);
        }

        $this->dispatch('reloadPowergrid');
    }

    private function exportMlFeedbackSamples(string $path): void
    {
        $columns = [
            'id',
            'disciplina',
            'orc_ml_std_id',
            'orc_ml_std_item_id',
            'ordem',
            'descricao_original',
            'ml_pred_json',
            'ml_prob_str',
            'ml_min_prob',
            'user_final_json',
            'was_edited',
            'edited_fields_json',
            'reason',
            'status',
            'created_by',
            'created_at',
            'updated_at',
        ];

        $rows = MlFeedbackSample::query()->get();

        $handle = fopen($path, 'w');
        $this->writeCsvRow($handle, $columns);

        foreach ($rows as $row) {
            $data = [];
            foreach ($columns as $col) {
                $data[] = $row->{$col};
            }
            $this->writeCsvRow($handle, $data);
        }

        fclose($handle);
    }

    private function writeCsvRow($handle, array $row): void
    {
        $escaped = array_map(function ($value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif ($value === null) {
                $value = '';
            }

            $value = (string) $value;
            $needsQuotes = strpbrk($value, ",\"\r\n") !== false;
            $value = str_replace('"', '""', $value);

            return $needsQuotes ? '"' . $value . '"' : $value;
        }, $row);

        fwrite($handle, implode(',', $escaped) . "\r\n");
    }

    private function exportDictsEle(string $dir): array
    {
        $map = [
            'dict_ele_tipo' => DictEleTipo::class,
            'dict_ele_material' => DictEleMaterial::class,
            'dict_ele_conexao' => DictEleConexao::class,
            'dict_ele_espessura' => DictEleEspessura::class,
            'dict_ele_extremidade' => DictEleExtremidade::class,
            'dict_ele_dimensao' => DictEleDimensao::class,
        ];

        return $this->exportDicts($dir, $map);
    }

    private function exportDictsTub(string $dir): array
    {
        $map = [
            'dict_tub_tipo' => DictTubTipo::class,
            'dict_tub_material' => DictTubMaterial::class,
            'dict_tub_schedule' => DictTubSchedule::class,
            'dict_tub_extremidade' => DictTubExtremidade::class,
            'dict_tub_diametro' => DictTubDiametro::class,
        ];

        return $this->exportDicts($dir, $map);
    }

    private function exportDicts(string $dir, array $map): array
    {
        $paths = [];
        $columns = ['Termo', 'Descricao_Padrao', 'Revisao'];

        foreach ($map as $name => $modelClass) {
            $path = $dir . "\\{$name}.csv";
            $paths[$name] = $path;

            $handle = fopen($path, 'w');
            $this->writeCsvRow($handle, $columns);

            $rows = $modelClass::query()->get($columns);
            foreach ($rows as $row) {
                $this->writeCsvRow($handle, [
                    $row->Termo,
                    $row->Descricao_Padrao,
                    $row->Revisao,
                ]);
            }

            fclose($handle);
        }

        return $paths;
    }

    private function upsertModeloMl(int $revisao, string $disciplina, string $jobId, array $payload): void
    {
        if ($revisao <= 0) {
            return;
        }

        $status = (string) ($payload['status'] ?? 'running');

        ModeloMl::updateOrCreate(
            [
                'disciplina' => $disciplina,
                'revisao' => $revisao,
            ],
            [
                'data' => Carbon::now()->format('Y-m-d'),
                'treino_job_id' => $jobId !== '' ? $jobId : null,
                'treino_status' => $status !== '' ? $status : 'running',
                'treino_created_at' => $payload['created_at'] ?? null,
                'treino_started_at' => $payload['started_at'] ?? null,
                'treino_finished_at' => $payload['finished_at'] ?? null,
                'treino_error' => $payload['error'] ?? null,
            ]
        );
    }
}
