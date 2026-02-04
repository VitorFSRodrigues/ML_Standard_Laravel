<?php

namespace App\Livewire\Powergrid;

use App\Models\MlFeedbackSample;
use App\Models\OrcMLstdItem;

use App\Models\StdELE;
use App\Models\StdEleTipo;
use App\Models\StdEleMaterial;
use App\Models\StdEleConexao;
use App\Models\StdEleEspessura;
use App\Models\StdEleExtremidade;
use App\Models\StdEleDimensao;

use App\Services\MlFeedbackService;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class AprovacaoELEGlobalTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
        'approveAllPageELE' => 'approveAllPage',
        // ✅ botões do header
        'toggleShowApprovedELE'  => 'toggleShowApproved',

        // ✅ botões das actions por linha
        'approveSampleELE'       => 'approveELE',
        'rejectSampleELE'        => 'rejectELE',
    ];

    public bool $showApproved = false;

    public string $tableName = 'aprovacao-ele-global-table';

    /** =========================================================
     *  ✅ CACHE: OPTIONS (para não consultar DB a cada render)
     *  ========================================================= */
    private ?array $cacheOptsTipo = null;
    private array $cacheOptsMaterialByTipo = [];
    private array $cacheOptsConexaoByKey = [];
    private array $cacheOptsEspessuraByKey = [];
    private array $cacheOptsExtremidadeByKey = [];
    private array $cacheOptsDimensaoByKey = [];

    /** =========================================================
     *  ✅ CACHE: STD (combinação -> std)
     *  ========================================================= */
    private array $cacheStdByKey = [];

    /** =========================================================
     *  ✅ CACHE: ID -> NOME (feedback sem N+1)
     *  ========================================================= */
    private ?array $nameTipoById = null;
    private ?array $nameMaterialById = null;
    private ?array $nameConexaoById = null;
    private ?array $nameEspessuraById = null;
    private ?array $nameExtremidadeById = null;
    private ?array $nameDimensaoById = null;

    public function reloadPowergrid(): void
    {
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
        $this->dispatch('$refresh');
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    /** =========================================================
     *  DATASOURCE (JOIN samples + itens)
     *  ========================================================= */
    public function datasource(): Builder
    {
        $q = MlFeedbackSample::query()
            ->from('ml_feedback_samples as s')
            ->join('orc_ml_std_itens as i', 'i.id', '=', 's.orc_ml_std_item_id')
            ->selectRaw('
                s.id as id,
                s.orc_ml_std_id as orc_ml_std_id,
                s.status as sample_status,
                s.reason as sample_reason,
                s.ml_min_prob as ml_min_prob,
                s.updated_at as sample_updated_at,

                i.id as item_id,
                i.descricao as descricao,
                i.prob as prob,
                i.std_ele as std_ele,
                i.user_edits as user_edits,

                i.std_ele_tipo_id,
                i.std_ele_material_id,
                i.std_ele_conexao_id,
                i.std_ele_espessura_id,
                i.std_ele_extremidade_id,
                i.std_ele_dimensao_id
            ')
            ->where('s.disciplina', 'ELE');

        if (!$this->showApproved) {
            $q->where('s.status', MlFeedbackSample::STATUS_NAO_REVISADO);
        }

        // ✅ ordenação sempre qualificada
        return $q->orderBy('s.updated_at', 'desc')
                ->orderBy('s.id', 'asc');
    }

    public function relationSearch(): array
    {
        return [];
    }

    /** =========================================================
     *  ✅ FILTERS EXTRAS (orc_ml_std_id, reason, min_prob)
     *  ========================================================= */
    public function filters(): array
    {
        return [
            Filter::inputText('descricao', 'i.descricao')
                ->placeholder('Descrição'),

            Filter::inputText('status_badge', 's.status')
                ->placeholder('Status'),

            // orc_ml_std_id = valor exato
            Filter::number('orc_ml_std_id', 's.orc_ml_std_id')
                ->builder(function (Builder $query, string $value) {
                    $v = (int) $value;
                    if ($v > 0) {
                        $query->where('s.orc_ml_std_id', $v);
                    }
                    return $query;
                }),

            // reason (select)
            Filter::select('sample_reason', 's.reason')
                ->dataSource([
                    ['value' => MlFeedbackSample::REASON_LOW_CONFIDENCE, 'label' => 'LOW_CONFIDENCE'],
                    ['value' => MlFeedbackSample::REASON_USER_EDIT,      'label' => 'USER_EDIT'],
                    ['value' => MlFeedbackSample::REASON_BOTH,           'label' => 'BOTH'],
                ])
                ->optionValue('value')
                ->optionLabel('label'),

            // ml_min_prob >= valor (mínimo)
            Filter::number('ml_min_prob', 's.ml_min_prob')
                ->builder(function (Builder $query, string $value) {
                    $v = (int) $value;
                    if ($v > 0) {
                        $query->where('s.ml_min_prob', '>=', $v);
                    }
                    return $query;
                }),
        ];
    }

    // =========================================================
    // PROB helpers (ELE: 6 itens)
    // =========================================================
    private function parseProb(?string $prob): array
    {
        if (!$prob) return [];
        return array_map(fn($x) => (int) trim($x), explode('/', $prob));
    }

    private function classByProb(int $prob, bool $userEdited = false): string
    {
        if ($userEdited) return 'pg-user-edit';
        if ($prob >= 90) return '';
        if ($prob >= 70) return 'pg-prob-mid';
        return 'pg-prob-low';
    }

    private function getUserEdits($row): array
    {
        $raw = $row->user_edits ?? null;
        if (!$raw) return [];

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function wasEdited($row, string $field): bool
    {
        $edits = $this->getUserEdits($row);
        return in_array($field, $edits, true);
    }

    // =========================================================
    // ✅ Warmup cache ID->Nome
    // =========================================================
    private function warmUpNameMaps(): void
    {
        if ($this->nameTipoById !== null) return;

        $this->nameTipoById        = StdEleTipo::query()->pluck('nome', 'id')->toArray();
        $this->nameMaterialById    = StdEleMaterial::query()->pluck('nome', 'id')->toArray();
        $this->nameConexaoById     = StdEleConexao::query()->pluck('nome', 'id')->toArray();
        $this->nameEspessuraById   = StdEleEspessura::query()->pluck('nome', 'id')->toArray();
        $this->nameExtremidadeById = StdEleExtremidade::query()->pluck('nome', 'id')->toArray();
        $this->nameDimensaoById    = StdEleDimensao::query()->pluck('nome', 'id')->toArray();
    }

    // =========================================================
    // OPTIONS CASCADE (COM CACHE)
    // =========================================================
    private function optsTipo(): array
    {
        if ($this->cacheOptsTipo !== null) return $this->cacheOptsTipo;

        return $this->cacheOptsTipo = StdEleTipo::query()
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    private function optsMaterial(?int $tipoId): array
    {
        $k = $tipoId ?? 0;

        if (isset($this->cacheOptsMaterialByTipo[$k])) {
            return $this->cacheOptsMaterialByTipo[$k];
        }

        if (!$tipoId) {
            return $this->cacheOptsMaterialByTipo[$k] = StdEleMaterial::query()
                ->orderBy('nome')
                ->pluck('nome', 'id')
                ->toArray();
        }

        $ids = StdELE::query()
            ->where('std_ele_tipo_id', $tipoId)
            ->distinct()
            ->pluck('std_ele_material_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->cacheOptsMaterialByTipo[$k] = StdEleMaterial::query()
            ->whereIn('id', $ids)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    private function optsConexao(?int $tipoId, ?int $materialId): array
    {
        $key = ($tipoId ?? 0) . '|' . ($materialId ?? 0);

        if (isset($this->cacheOptsConexaoByKey[$key])) {
            return $this->cacheOptsConexaoByKey[$key];
        }

        $q = StdELE::query();

        if ($tipoId) $q->where('std_ele_tipo_id', $tipoId);
        if ($materialId) $q->where('std_ele_material_id', $materialId);

        $ids = $q->distinct()
            ->pluck('std_ele_conexao_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->cacheOptsConexaoByKey[$key] = StdEleConexao::query()
            ->whereIn('id', $ids)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    private function optsEspessura(?int $tipoId, ?int $materialId, ?int $conexaoId): array
    {
        $key = ($tipoId ?? 0) . '|' . ($materialId ?? 0) . '|' . ($conexaoId ?? 0);

        if (isset($this->cacheOptsEspessuraByKey[$key])) {
            return $this->cacheOptsEspessuraByKey[$key];
        }

        $q = StdELE::query();
        if ($tipoId) $q->where('std_ele_tipo_id', $tipoId);
        if ($materialId) $q->where('std_ele_material_id', $materialId);
        if ($conexaoId) $q->where('std_ele_conexao_id', $conexaoId);

        $ids = $q->distinct()
            ->pluck('std_ele_espessura_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->cacheOptsEspessuraByKey[$key] = StdEleEspessura::query()
            ->whereIn('id', $ids)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    private function optsExtremidade(?int $tipoId, ?int $materialId, ?int $conexaoId, ?int $espessuraId): array
    {
        $key = ($tipoId ?? 0) . '|' . ($materialId ?? 0) . '|' . ($conexaoId ?? 0) . '|' . ($espessuraId ?? 0);

        if (isset($this->cacheOptsExtremidadeByKey[$key])) {
            return $this->cacheOptsExtremidadeByKey[$key];
        }

        $q = StdELE::query();
        if ($tipoId) $q->where('std_ele_tipo_id', $tipoId);
        if ($materialId) $q->where('std_ele_material_id', $materialId);
        if ($conexaoId) $q->where('std_ele_conexao_id', $conexaoId);
        if ($espessuraId) $q->where('std_ele_espessura_id', $espessuraId);

        $ids = $q->distinct()
            ->pluck('std_ele_extremidade_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->cacheOptsExtremidadeByKey[$key] = StdEleExtremidade::query()
            ->whereIn('id', $ids)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    private function optsDimensao(?int $tipoId, ?int $materialId, ?int $conexaoId, ?int $espessuraId, ?int $extremidadeId): array
    {
        $key = ($tipoId ?? 0) . '|' . ($materialId ?? 0) . '|' . ($conexaoId ?? 0) . '|' . ($espessuraId ?? 0) . '|' . ($extremidadeId ?? 0);

        if (isset($this->cacheOptsDimensaoByKey[$key])) {
            return $this->cacheOptsDimensaoByKey[$key];
        }

        $q = StdELE::query();
        if ($tipoId) $q->where('std_ele_tipo_id', $tipoId);
        if ($materialId) $q->where('std_ele_material_id', $materialId);
        if ($conexaoId) $q->where('std_ele_conexao_id', $conexaoId);
        if ($espessuraId) $q->where('std_ele_espessura_id', $espessuraId);
        if ($extremidadeId) $q->where('std_ele_extremidade_id', $extremidadeId);

        $ids = $q->distinct()
            ->pluck('std_ele_dimensao_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->cacheOptsDimensaoByKey[$key] = StdEleDimensao::query()
            ->whereIn('id', $ids)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    private function optionHtml(array $options, ?int $selectedId): string
    {
        $html = '<option value="">--</option>';

        foreach ($options as $id => $label) {
            $sel = ((int)$id === (int)$selectedId) ? 'selected' : '';
            $html .= "<option value=\"{$id}\" {$sel}>" . e($label) . "</option>";
        }

        return $html;
    }

    private function firstOptionId(array $options): ?int
    {
        if (empty($options)) return null;
        $firstKey = array_key_first($options);
        return $firstKey ? (int) $firstKey : null;
    }

    /** =========================================================
     *  ✅ Cache STD (combinação -> std)
     *  ========================================================= */
    private function stdKey(int $tipo, int $material, int $conexao, int $espessura, int $extremidade, int $dimensao): string
    {
        return implode('|', [$tipo, $material, $conexao, $espessura, $extremidade, $dimensao]);
    }

    private function resolveStdCached(OrcMLstdItem $item): ?float
    {
        if (
            !$item->std_ele_tipo_id ||
            !$item->std_ele_material_id ||
            !$item->std_ele_conexao_id ||
            !$item->std_ele_espessura_id ||
            !$item->std_ele_extremidade_id ||
            !$item->std_ele_dimensao_id
        ) {
            return null;
        }

        $key = $this->stdKey(
            (int)$item->std_ele_tipo_id,
            (int)$item->std_ele_material_id,
            (int)$item->std_ele_conexao_id,
            (int)$item->std_ele_espessura_id,
            (int)$item->std_ele_extremidade_id,
            (int)$item->std_ele_dimensao_id
        );

        if (array_key_exists($key, $this->cacheStdByKey)) {
            return $this->cacheStdByKey[$key];
        }

        $std = StdELE::query()
            ->where('std_ele_tipo_id', $item->std_ele_tipo_id)
            ->where('std_ele_material_id', $item->std_ele_material_id)
            ->where('std_ele_conexao_id', $item->std_ele_conexao_id)
            ->where('std_ele_espessura_id', $item->std_ele_espessura_id)
            ->where('std_ele_extremidade_id', $item->std_ele_extremidade_id)
            ->where('std_ele_dimensao_id', $item->std_ele_dimensao_id)
            ->value('std');

        return $this->cacheStdByKey[$key] = $std;
    }

    /** =========================================================
     *  ✅ UPDATE (edita item + grava feedback USER_EDIT)
     *  ========================================================= */
    public function updateEleCell(int $itemId, string $field, $value): void
    {
        $value = $value === '' ? null : (int) $value;

        /** @var OrcMLstdItem|null $item */
        $item = OrcMLstdItem::query()->find($itemId);
        if (!$item) return;

        $chain = [
            'std_ele_tipo_id',
            'std_ele_material_id',
            'std_ele_conexao_id',
            'std_ele_espessura_id',
            'std_ele_extremidade_id',
            'std_ele_dimensao_id',
        ];

        // aplica mudança do usuário
        $item->{$field} = $value;

        // reseta filhos
        $idx = array_search($field, $chain, true);
        if ($idx !== false) {
            for ($i = $idx + 1; $i < count($chain); $i++) {
                $item->{$chain[$i]} = null;
            }
        }

        // auto-preenche filhos (se não limpou)
        if ($value !== null && $idx !== false) {

            if ($field === 'std_ele_tipo_id') {
                $opts = $this->optsMaterial((int) $item->std_ele_tipo_id);
                $item->std_ele_material_id = $this->firstOptionId($opts);
            }

            if (in_array($field, ['std_ele_tipo_id', 'std_ele_material_id'], true)) {
                if ($item->std_ele_tipo_id && $item->std_ele_material_id) {
                    $opts = $this->optsConexao((int)$item->std_ele_tipo_id, (int)$item->std_ele_material_id);
                    $item->std_ele_conexao_id = $this->firstOptionId($opts);
                }
            }

            if (in_array($field, ['std_ele_tipo_id', 'std_ele_material_id', 'std_ele_conexao_id'], true)) {
                if ($item->std_ele_tipo_id && $item->std_ele_material_id && $item->std_ele_conexao_id) {
                    $opts = $this->optsEspessura(
                        (int)$item->std_ele_tipo_id,
                        (int)$item->std_ele_material_id,
                        (int)$item->std_ele_conexao_id
                    );
                    $item->std_ele_espessura_id = $this->firstOptionId($opts);
                }
            }

            if (in_array($field, ['std_ele_tipo_id', 'std_ele_material_id', 'std_ele_conexao_id', 'std_ele_espessura_id'], true)) {
                if ($item->std_ele_tipo_id && $item->std_ele_material_id && $item->std_ele_conexao_id && $item->std_ele_espessura_id) {
                    $opts = $this->optsExtremidade(
                        (int)$item->std_ele_tipo_id,
                        (int)$item->std_ele_material_id,
                        (int)$item->std_ele_conexao_id,
                        (int)$item->std_ele_espessura_id
                    );
                    $item->std_ele_extremidade_id = $this->firstOptionId($opts);
                }
            }

            if (in_array($field, [
                'std_ele_tipo_id', 'std_ele_material_id', 'std_ele_conexao_id',
                'std_ele_espessura_id', 'std_ele_extremidade_id'
            ], true)) {
                if (
                    $item->std_ele_tipo_id && $item->std_ele_material_id &&
                    $item->std_ele_conexao_id && $item->std_ele_espessura_id &&
                    $item->std_ele_extremidade_id
                ) {
                    $opts = $this->optsDimensao(
                        (int)$item->std_ele_tipo_id,
                        (int)$item->std_ele_material_id,
                        (int)$item->std_ele_conexao_id,
                        (int)$item->std_ele_espessura_id,
                        (int)$item->std_ele_extremidade_id
                    );
                    $item->std_ele_dimensao_id = $this->firstOptionId($opts);
                }
            }
        }

        // registra edit do user no campo alterado
        if (Schema::hasColumn('orc_ml_std_itens', 'user_edits')) {
            $edits = $this->getUserEdits($item);

            if (!in_array($field, $edits, true)) {
                $edits[] = $field;
            }

            $item->user_edits = json_encode(array_values($edits));
        }

        // recalcula STD (cache)
        $item->std_ele = $this->resolveStdCached($item);
        $item->save();

        // feedback user edit
        $this->warmUpNameMaps();

        $userFinalNames = [
            'tipo'        => $item->std_ele_tipo_id ? ($this->nameTipoById[$item->std_ele_tipo_id] ?? null) : null,
            'material'    => $item->std_ele_material_id ? ($this->nameMaterialById[$item->std_ele_material_id] ?? null) : null,
            'conexao'     => $item->std_ele_conexao_id ? ($this->nameConexaoById[$item->std_ele_conexao_id] ?? null) : null,
            'espessura'   => $item->std_ele_espessura_id ? ($this->nameEspessuraById[$item->std_ele_espessura_id] ?? null) : null,
            'extremidade' => $item->std_ele_extremidade_id ? ($this->nameExtremidadeById[$item->std_ele_extremidade_id] ?? null) : null,
            'dimensao'    => $item->std_ele_dimensao_id ? ($this->nameDimensaoById[$item->std_ele_dimensao_id] ?? null) : null,
        ];

        $userFinalNames = array_map(
            fn($v) => $v ? mb_strtoupper(trim($v), 'UTF-8') : null,
            $userFinalNames
        );

        app(MlFeedbackService::class)->storeUserEdit(
            $item,
            $userFinalNames,
            [$field],
            auth()->id()
        );

        $this->dispatch('reloadPowergrid');
    }

    /** =========================================================
     *  ✅ APROVAR / REPROVAR (por linha)
     *  ========================================================= */
    public function approveELE($payload = null): void
    {
        $sampleId = is_array($payload)
            ? (int) ($payload['id'] ?? 0)
            : (int) ($payload ?? 0);

        if ($sampleId <= 0) return;

        MlFeedbackSample::query()
            ->where('id', $sampleId)
            ->update([
                'status'     => MlFeedbackSample::STATUS_APROVADO,
                'updated_at' => now(),
            ]);

        $this->dispatch('reloadPowergrid');
    }

    public function rejectELE($payload = null): void
    {
        $sampleId = is_array($payload)
            ? (int) ($payload['id'] ?? 0)
            : (int) ($payload ?? 0);

        if ($sampleId <= 0) return;

        MlFeedbackSample::query()
            ->where('id', $sampleId)
            ->update([
                'status'     => MlFeedbackSample::STATUS_REPROVADO,
                'updated_at' => now(),
            ]);

        $this->dispatch('reloadPowergrid');
    }

    /** =========================================================
     *  ✅ APROVAR TUDO DA PÁGINA (a página atual do PowerGrid)
     *  ========================================================= */
    public function approveCurrentPage(): void
    {
        if ($this->showApproved ?? false) {
            return;
        }

        // tenta usar estado do componente; fallback via request
        $page = (int) (property_exists($this, 'page') ? $this->page : request()->get('page', 1));
        $perPage = (int) (property_exists($this, 'perPage') ? $this->perPage : request()->get('perPage', 10));

        $ids = MlFeedbackSample::query()
            ->from('ml_feedback_samples as s')
            ->where('s.disciplina', 'ELE')
            ->where('s.status', MlFeedbackSample::STATUS_NAO_REVISADO)
            ->orderBy('s.updated_at', 'desc')
            ->orderBy('s.id', 'asc')
            ->forPage($page, $perPage)
            ->pluck('s.id')
            ->toArray();

        if (!empty($ids)) {
            MlFeedbackSample::query()
                ->whereIn('id', $ids)
                ->update(['status' => MlFeedbackSample::STATUS_APROVADO]);
        }

        $this->dispatch('reloadPowergrid');
    }

    // =========================================================
    // FIELDS + COLUMNS
    // =========================================================
    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('sample_id', fn ($row) => $row->id)
            ->add('orc_ml_std_id')
            ->add('ml_min_prob')
            ->add('sample_reason')
            ->add('sample_status')

            ->add('descricao', function ($row) {
                $full = (string)($row->descricao ?? '');
                $short = Str::limit($full, 140, '...');

                return new HtmlString(
                    '<div title="' . e($full) . '" style="white-space:normal;word-break:break-word;max-width:520px;">'
                    . e($short) .
                    '</div>'
                );
            })

            ->add('tipo_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[0] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_ele_tipo_id'));

                $options = $this->optsTipo();
                $optHtml = $this->optionHtml($options, (int)($row->std_ele_tipo_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateEleCell({$row->item_id}, 'std_ele_tipo_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('material_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[1] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_ele_material_id'));

                $tipoId = $row->std_ele_tipo_id ? (int)$row->std_ele_tipo_id : null;
                $options = $this->optsMaterial($tipoId);
                $optHtml = $this->optionHtml($options, (int)($row->std_ele_material_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateEleCell({$row->item_id}, 'std_ele_material_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('conexao_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[2] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_ele_conexao_id'));

                $tipoId = $row->std_ele_tipo_id ? (int)$row->std_ele_tipo_id : null;
                $materialId = $row->std_ele_material_id ? (int)$row->std_ele_material_id : null;

                $options = $this->optsConexao($tipoId, $materialId);
                $optHtml = $this->optionHtml($options, (int)($row->std_ele_conexao_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateEleCell({$row->item_id}, 'std_ele_conexao_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('espessura_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[3] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_ele_espessura_id'));

                $tipoId = $row->std_ele_tipo_id ? (int)$row->std_ele_tipo_id : null;
                $materialId = $row->std_ele_material_id ? (int)$row->std_ele_material_id : null;
                $conexaoId = $row->std_ele_conexao_id ? (int)$row->std_ele_conexao_id : null;

                $options = $this->optsEspessura($tipoId, $materialId, $conexaoId);
                $optHtml = $this->optionHtml($options, (int)($row->std_ele_espessura_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateEleCell({$row->item_id}, 'std_ele_espessura_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('extremidade_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[4] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_ele_extremidade_id'));

                $tipoId = $row->std_ele_tipo_id ? (int)$row->std_ele_tipo_id : null;
                $materialId = $row->std_ele_material_id ? (int)$row->std_ele_material_id : null;
                $conexaoId = $row->std_ele_conexao_id ? (int)$row->std_ele_conexao_id : null;
                $espessuraId = $row->std_ele_espessura_id ? (int)$row->std_ele_espessura_id : null;

                $options = $this->optsExtremidade($tipoId, $materialId, $conexaoId, $espessuraId);
                $optHtml = $this->optionHtml($options, (int)($row->std_ele_extremidade_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateEleCell({$row->item_id}, 'std_ele_extremidade_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('dimensao_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[5] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_ele_dimensao_id'));

                $tipoId = $row->std_ele_tipo_id ? (int)$row->std_ele_tipo_id : null;
                $materialId = $row->std_ele_material_id ? (int)$row->std_ele_material_id : null;
                $conexaoId = $row->std_ele_conexao_id ? (int)$row->std_ele_conexao_id : null;
                $espessuraId = $row->std_ele_espessura_id ? (int)$row->std_ele_espessura_id : null;
                $extremidadeId = $row->std_ele_extremidade_id ? (int)$row->std_ele_extremidade_id : null;

                $options = $this->optsDimensao($tipoId, $materialId, $conexaoId, $espessuraId, $extremidadeId);
                $optHtml = $this->optionHtml($options, (int)($row->std_ele_dimensao_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateEleCell({$row->item_id}, 'std_ele_dimensao_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('prob', fn($row) => $row->prob ?? '')
            ->add('std', fn($row) => $row->std_ele ?? '')

            ->add('status_badge', function ($row) {
                $st = (string)($row->sample_status ?? '');
                $cls = $st === MlFeedbackSample::STATUS_APROVADO ? 'badge badge-success' : 'badge badge-danger';
                return new HtmlString("<span class=\"{$cls}\">{$st}</span>");
            });
    }

    public function columns(): array
    {
        return [
            // Column::make('SampleID', 'sample_id')->sortable(),
            // Column::make('OrcID', 'orc_ml_std_id')->sortable(),

            Column::make('Descrição', 'descricao')->searchable(),

            Column::make('TIPO', 'tipo_edit'),
            Column::make('MATERIAL', 'material_edit'),
            Column::make('CONEXÃO', 'conexao_edit'),
            Column::make('ESPESSURA', 'espessura_edit'),
            Column::make('EXTREMIDADE', 'extremidade_edit'),
            Column::make('DIMENSÃO', 'dimensao_edit'),

            // Column::make('MinProb', 'ml_min_prob')->sortable(),
            // Column::make('Motivo', 'sample_reason')->sortable(),

            Column::make('Status', 'status_badge'),

            Column::action('Actions'),
        ];
    }

    public function header(): array
    {
        return [
            Button::add('toggle')
                ->slot($this->showApproved ? 'Mostrar Não Revisados' : 'Mostrar Todos')
                ->class('btn btn-outline-primary')
                ->dispatch('toggleShowApprovedELE', []),

            Button::add('approve_all')
                ->slot('Aprovar Tudo da Página')
                ->class('btn btn-sm btn-success')
                ->dispatch('approveAllPageELE', []),
        ];
    }

    public function toggleShowApproved(): void
    {
        $this->showApproved = !$this->showApproved;
        $this->dispatch('reloadPowergrid');
    }

    public function actions($row): array
    {
        return [
            Button::add('approve')
            ->slot('Aprovar')
            ->class('btn btn-sm btn-success')
            ->dispatch('approveSampleELE', [$row->id]),

            Button::add('reject')
                ->slot('Reprovar')
                ->class('btn btn-sm btn-danger')
                ->dispatch('rejectSampleELE', [$row->id]),
        ];
    }

    /**
     * ✅ Aprova tudo da página atual (respeitando filtros e paginação do PowerGrid).
     */
    public function approveAllPage(): void
    {
        // Se estiver mostrando aprovados, não faz sentido aprovar tudo
        if ($this->showApproved ?? false) {
            return;
        }

        [$page, $perPage] = $this->currentPagination();

        // Monta a query base (MESMA do datasource)
        $q = $this->baseApprovalQueryELE();

        // IDs da página atual
        $ids = (clone $q)
            ->forPage($page, $perPage)
            ->pluck('s.id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->toArray();

        if (empty($ids)) {
            return;
        }

        // ✅ atualiza em lote
        MlFeedbackSample::query()
            ->whereIn('id', $ids)
            ->update([
                'status'     => MlFeedbackSample::STATUS_APROVADO,
                'updated_at' => now(), // importante para order by updated_at
            ]);

        $this->dispatch('reloadPowergrid');
    }

    /**
     * ✅ Query base da grid ELE (com filtros extras).
     * Essa é a mesma lógica do datasource(),
     * porém com as condições de filtros reaplicadas.
     */
    private function baseApprovalQueryELE(): Builder
    {
        $q = MlFeedbackSample::query()
            ->from('ml_feedback_samples as s')
            ->join('orc_ml_std_itens as i', 'i.id', '=', 's.orc_ml_std_item_id')
            ->where('s.disciplina', 'ELE');

        // status conforme toggle (mostrar todos ou apenas nÃ£o revisados)
        if (!($this->showApproved ?? false)) {
            $q->where('s.status', MlFeedbackSample::STATUS_NAO_REVISADO);
        }

        // =========================
        // ✅ FILTROS EXTRAS (safe)
        // =========================
        $orcId = $this->pgFilterValue(['orc_ml_std_id', 's.orc_ml_std_id']);
        if (is_numeric($orcId)) {
            $q->where('s.orc_ml_std_id', (int) $orcId);
        }

        $reason = $this->pgFilterValue(['reason', 'sample_reason', 's.reason']);
        if (is_string($reason) && $reason !== '') {
            $q->where('s.reason', $reason);
        }

        $minProb = $this->pgFilterValue(['ml_min_prob', 'min_prob', 's.ml_min_prob']);
        if (is_numeric($minProb)) {
            // ✅ min_prob geralmente é filtro por ">= X"
            $q->where('s.ml_min_prob', '>=', (int) $minProb);
        }

        $descricao = $this->pgFilterValue(['descricao', 'i.descricao']);
        if (is_string($descricao) && $descricao !== '') {
            $q->where('i.descricao', 'like', '%' . $descricao . '%');
        }

        $status = $this->pgFilterValue(['status_badge', 'sample_status', 's.status']);
        if (is_string($status) && $status !== '') {
            $q->where('s.status', 'like', '%' . $status . '%');
        }

        // ✅ ordenação sempre QUALIFICADA (evita ambiguous id)
        return $q->orderBy('s.updated_at', 'desc')
                ->orderBy('s.id', 'asc');
    }

    /**
     * ✅ Tenta capturar valor de filtros aplicados no PowerGrid
     * sem depender do formato exato (varia por versão).
     */
    private function pgFilterValue(array $possibleKeys)
    {
        // 1) tenta ler de $this->filters (padrão PowerGrid)
        $filters = $this->filters ?? null;

        if (is_array($filters)) {
            foreach ($possibleKeys as $k) {
                if (!array_key_exists($k, $filters)) continue;

                $f = $filters[$k];

                // formato array
                if (is_array($f)) {
                    if (array_key_exists('value', $f)) return $f['value'];
                    if (array_key_exists('search', $f)) return $f['search'];
                }

                // formato objeto
                if (is_object($f)) {
                    if (property_exists($f, 'value')) return $f->value;
                    if (property_exists($f, 'search')) return $f->search;
                }
            }
        }

        // 2) fallback: tenta ler como propriedades públicas (se você usar esse padrão)
        foreach ($possibleKeys as $k) {
            $prop = Str::camel(str_replace(['s.', 'sample_'], '', $k));
            if (property_exists($this, $prop) && $this->{$prop} !== null && $this->{$prop} !== '') {
                return $this->{$prop};
            }
        }

        return null;
    }

    /**
     * ✅ Descobre pagina atual e perPage do PowerGrid/Livewire.
     */
    private function currentPagination(): array
    {
        // Livewire WithPagination normalmente usa $page
        $page = (int) ($this->page ?? 1);

        // PowerGrid guarda perPage em props diferentes dependendo da config
        $perPage = $this->perPage ?? null;
        if (!$perPage && property_exists($this, 'perPageValue')) {
            $perPage = $this->perPageValue;
        }
        if (!$perPage) {
            $perPage = 10; // fallback
        }

        return [$page, (int) $perPage];
    }
}
