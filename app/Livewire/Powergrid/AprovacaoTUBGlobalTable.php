<?php

namespace App\Livewire\Powergrid;

use App\Models\MlFeedbackSample;
use App\Models\OrcMLstdItem;

use App\Models\StdTUB;
use App\Models\StdTubTipo;
use App\Models\StdTubMaterial;
use App\Models\StdTubSchedule;
use App\Models\StdTubExtremidade;
use App\Models\StdTubDiametro;

use App\Services\MlFeedbackService;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class AprovacaoTUBGlobalTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',

        // ✅ Aprovar tudo da página
        'approveAllPageTUB'      => 'approveAllPage',

        // ✅ Toggle header
        'toggleShowApprovedTUB'  => 'toggleShowApproved',

        // ✅ Actions por linha
        'approveSampleTUB'       => 'approveTUB',
        'rejectSampleTUB'        => 'rejectTUB',
    ];

    public bool $showApproved = false;

    public string $tableName = 'aprovacao-tub-global-table';

    /** =========================================================
     *  ✅ CACHE: OPTIONS
     *  ========================================================= */
    private ?array $cacheOptsTipo = null;
    private array $cacheOptsMaterialByTipo = [];
    private array $cacheOptsScheduleByKey = [];
    private array $cacheOptsExtremidadeByKey = [];
    private array $cacheOptsDiametroByKey = [];

    /** =========================================================
     *  ✅ CACHE: Valores STD_TUB
     *  ========================================================= */
    private array $cacheTubValsByKey = [];

    /** =========================================================
     *  ✅ CACHE: ID -> NOME
     *  ========================================================= */
    private ?array $nameTipoById = null;
    private ?array $nameMaterialById = null;
    private ?array $nameScheduleById = null;
    private ?array $nameExtremidadeById = null;
    private ?array $nameDiametroById = null;

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
                i.hh_un as hh_un,
                i.kg_hh as kg_hh,
                i.kg_un as kg_un,
                i.m2_un as m2_un,
                i.user_edits as user_edits,

                i.std_tub_tipo_id,
                i.std_tub_material_id,
                i.std_tub_schedule_id,
                i.std_tub_extremidade_id,
                i.std_tub_diametro_id
            ')
            ->where('s.disciplina', 'TUB');

        if (!$this->showApproved) {
            $q->where('s.status', MlFeedbackSample::STATUS_NAO_REVISADO);
        }

        // ✅ ordenação qualificada
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

            Filter::number('orc_ml_std_id', 's.orc_ml_std_id')
                ->builder(function (Builder $query, string $value) {
                    $v = (int) $value;
                    if ($v > 0) {
                        $query->where('s.orc_ml_std_id', $v);
                    }
                    return $query;
                }),

            Filter::select('sample_reason', 's.reason')
                ->dataSource([
                    ['value' => MlFeedbackSample::REASON_LOW_CONFIDENCE, 'label' => 'LOW_CONFIDENCE'],
                    ['value' => MlFeedbackSample::REASON_USER_EDIT,      'label' => 'USER_EDIT'],
                    ['value' => MlFeedbackSample::REASON_BOTH,           'label' => 'BOTH'],
                ])
                ->optionValue('value')
                ->optionLabel('label'),

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
    // PROB helpers (TUB: 5 itens)
    // ordem: tipo/material/schedule/extremidade/diametro
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

    private function warmUpNameMaps(): void
    {
        if ($this->nameTipoById !== null) return;

        $this->nameTipoById        = StdTubTipo::query()->pluck('nome', 'id')->toArray();
        $this->nameMaterialById    = StdTubMaterial::query()->pluck('nome', 'id')->toArray();
        $this->nameScheduleById    = StdTubSchedule::query()->pluck('nome', 'id')->toArray();
        $this->nameExtremidadeById = StdTubExtremidade::query()->pluck('nome', 'id')->toArray();
        $this->nameDiametroById    = StdTubDiametro::query()->pluck('nome', 'id')->toArray();
    }

    // =========================================================
    // OPTIONS CASCADE (COM CACHE)
    // =========================================================
    private function optsTipo(): array
    {
        if ($this->cacheOptsTipo !== null) return $this->cacheOptsTipo;

        return $this->cacheOptsTipo = StdTubTipo::query()
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
            return $this->cacheOptsMaterialByTipo[$k] = StdTubMaterial::query()
                ->orderBy('nome')
                ->pluck('nome', 'id')
                ->toArray();
        }

        $ids = StdTUB::query()
            ->where('std_tub_tipo_id', $tipoId)
            ->distinct()
            ->pluck('std_tub_material_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->cacheOptsMaterialByTipo[$k] = StdTubMaterial::query()
            ->whereIn('id', $ids)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    private function optsSchedule(?int $tipoId, ?int $materialId): array
    {
        $key = ($tipoId ?? 0) . '|' . ($materialId ?? 0);

        if (isset($this->cacheOptsScheduleByKey[$key])) {
            return $this->cacheOptsScheduleByKey[$key];
        }

        $q = StdTUB::query();
        if ($tipoId) $q->where('std_tub_tipo_id', $tipoId);
        if ($materialId) $q->where('std_tub_material_id', $materialId);

        $ids = $q->distinct()
            ->pluck('std_tub_schedule_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->cacheOptsScheduleByKey[$key] = StdTubSchedule::query()
            ->whereIn('id', $ids)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    private function optsExtremidade(?int $tipoId, ?int $materialId, ?int $scheduleId): array
    {
        $key = ($tipoId ?? 0) . '|' . ($materialId ?? 0) . '|' . ($scheduleId ?? 0);

        if (isset($this->cacheOptsExtremidadeByKey[$key])) {
            return $this->cacheOptsExtremidadeByKey[$key];
        }

        $q = StdTUB::query();
        if ($tipoId) $q->where('std_tub_tipo_id', $tipoId);
        if ($materialId) $q->where('std_tub_material_id', $materialId);
        if ($scheduleId) $q->where('std_tub_schedule_id', $scheduleId);

        $ids = $q->distinct()
            ->pluck('std_tub_extremidade_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->cacheOptsExtremidadeByKey[$key] = StdTubExtremidade::query()
            ->whereIn('id', $ids)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    private function optsDiametro(?int $tipoId, ?int $materialId, ?int $scheduleId, ?int $extremidadeId): array
    {
        $key = ($tipoId ?? 0) . '|' . ($materialId ?? 0) . '|' . ($scheduleId ?? 0) . '|' . ($extremidadeId ?? 0);

        if (isset($this->cacheOptsDiametroByKey[$key])) {
            return $this->cacheOptsDiametroByKey[$key];
        }

        $q = StdTUB::query();
        if ($tipoId) $q->where('std_tub_tipo_id', $tipoId);
        if ($materialId) $q->where('std_tub_material_id', $materialId);
        if ($scheduleId) $q->where('std_tub_schedule_id', $scheduleId);
        if ($extremidadeId) $q->where('std_tub_extremidade_id', $extremidadeId);

        $ids = $q->distinct()
            ->pluck('std_tub_diametro_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->cacheOptsDiametroByKey[$key] = StdTubDiametro::query()
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

    // =========================================================
    // ✅ Cache valores da combinação TUB
    // =========================================================
    private function tubKey(int $tipo, int $material, int $schedule, int $extremidade, int $diametro): string
    {
        return implode('|', [$tipo, $material, $schedule, $extremidade, $diametro]);
    }

    private function resolveTubValsCached(OrcMLstdItem $item): array
    {
        $vals = [null, null, null, null];

        if (
            !$item->std_tub_tipo_id ||
            !$item->std_tub_material_id ||
            !$item->std_tub_schedule_id ||
            !$item->std_tub_extremidade_id ||
            !$item->std_tub_diametro_id
        ) {
            return $vals;
        }

        $key = $this->tubKey(
            (int)$item->std_tub_tipo_id,
            (int)$item->std_tub_material_id,
            (int)$item->std_tub_schedule_id,
            (int)$item->std_tub_extremidade_id,
            (int)$item->std_tub_diametro_id
        );

        if (array_key_exists($key, $this->cacheTubValsByKey)) {
            return $this->cacheTubValsByKey[$key];
        }

        $row = StdTUB::query()
            ->where('std_tub_tipo_id', $item->std_tub_tipo_id)
            ->where('std_tub_material_id', $item->std_tub_material_id)
            ->where('std_tub_schedule_id', $item->std_tub_schedule_id)
            ->where('std_tub_extremidade_id', $item->std_tub_extremidade_id)
            ->where('std_tub_diametro_id', $item->std_tub_diametro_id)
            ->first(['hh_un', 'kg_hh', 'kg_un', 'm2_un']);

        if ($row) {
            $vals = [$row->hh_un, $row->kg_hh, $row->kg_un, $row->m2_un];
        }

        return $this->cacheTubValsByKey[$key] = $vals;
    }

    // =========================================================
    // ✅ UPDATE (edita item + grava feedback USER_EDIT)
    // =========================================================
    public function updateTubCell(int $itemId, string $field, $value): void
    {
        $value = $value === '' ? null : (int) $value;

        /** @var OrcMLstdItem|null $item */
        $item = OrcMLstdItem::query()->find($itemId);
        if (!$item) return;

        $chain = [
            'std_tub_tipo_id',
            'std_tub_material_id',
            'std_tub_schedule_id',
            'std_tub_extremidade_id',
            'std_tub_diametro_id',
        ];

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

            if ($field === 'std_tub_tipo_id') {
                $opts = $this->optsMaterial((int) $item->std_tub_tipo_id);
                $item->std_tub_material_id = $this->firstOptionId($opts);
            }

            if (in_array($field, ['std_tub_tipo_id', 'std_tub_material_id'], true)) {
                if ($item->std_tub_tipo_id && $item->std_tub_material_id) {
                    $opts = $this->optsSchedule((int)$item->std_tub_tipo_id, (int)$item->std_tub_material_id);
                    $item->std_tub_schedule_id = $this->firstOptionId($opts);
                }
            }

            if (in_array($field, ['std_tub_tipo_id', 'std_tub_material_id', 'std_tub_schedule_id'], true)) {
                if ($item->std_tub_tipo_id && $item->std_tub_material_id && $item->std_tub_schedule_id) {
                    $opts = $this->optsExtremidade(
                        (int)$item->std_tub_tipo_id,
                        (int)$item->std_tub_material_id,
                        (int)$item->std_tub_schedule_id
                    );
                    $item->std_tub_extremidade_id = $this->firstOptionId($opts);
                }
            }

            if (in_array($field, [
                'std_tub_tipo_id', 'std_tub_material_id', 'std_tub_schedule_id', 'std_tub_extremidade_id'
            ], true)) {
                if (
                    $item->std_tub_tipo_id &&
                    $item->std_tub_material_id &&
                    $item->std_tub_schedule_id &&
                    $item->std_tub_extremidade_id
                ) {
                    $opts = $this->optsDiametro(
                        (int)$item->std_tub_tipo_id,
                        (int)$item->std_tub_material_id,
                        (int)$item->std_tub_schedule_id,
                        (int)$item->std_tub_extremidade_id
                    );
                    $item->std_tub_diametro_id = $this->firstOptionId($opts);
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

        // recalcula valores TUB (cache)
        [$hh_un, $kg_hh, $kg_un, $m2_un] = $this->resolveTubValsCached($item);

        $item->hh_un = $hh_un;
        $item->kg_hh = $kg_hh;
        $item->kg_un = $kg_un;
        $item->m2_un = $m2_un;

        $item->save();

        // feedback user edit
        $this->warmUpNameMaps();

        $userFinalNames = [
            'tipo'        => $item->std_tub_tipo_id ? ($this->nameTipoById[$item->std_tub_tipo_id] ?? null) : null,
            'material'    => $item->std_tub_material_id ? ($this->nameMaterialById[$item->std_tub_material_id] ?? null) : null,
            'schedule'    => $item->std_tub_schedule_id ? ($this->nameScheduleById[$item->std_tub_schedule_id] ?? null) : null,
            'extremidade' => $item->std_tub_extremidade_id ? ($this->nameExtremidadeById[$item->std_tub_extremidade_id] ?? null) : null,
            'diametro'    => $item->std_tub_diametro_id ? ($this->nameDiametroById[$item->std_tub_diametro_id] ?? null) : null,
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

    // =========================================================
    // ✅ APROVAR / REPROVAR (por linha)
    // =========================================================
    public function approveTUB($payload = null): void
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

    public function rejectTUB($payload = null): void
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

    // =========================================================
    // FIELDS + COLUMNS
    // =========================================================
    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('sample_id', fn($row) => $row->id)
            ->add('orc_ml_std_id')
            ->add('ml_min_prob')
            ->add('sample_reason')
            ->add('sample_status')

            ->add('descricao', function ($row) {
                $full = (string)($row->descricao ?? '');
                $short = Str::limit($full, 120, '...');

                return new HtmlString(
                    '<div title="' . e($full) . '" style="white-space:normal;word-break:break-word;max-width:520px;">'
                    . e($short) .
                    '</div>'
                );
            })

            ->add('tipo_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[0] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_tub_tipo_id'));

                $options = $this->optsTipo();
                $optHtml = $this->optionHtml($options, (int)($row->std_tub_tipo_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateTubCell({$row->item_id}, 'std_tub_tipo_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('material_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[1] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_tub_material_id'));

                $tipoId = $row->std_tub_tipo_id ? (int)$row->std_tub_tipo_id : null;
                $options = $this->optsMaterial($tipoId);
                $optHtml = $this->optionHtml($options, (int)($row->std_tub_material_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateTubCell({$row->item_id}, 'std_tub_material_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('schedule_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[2] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_tub_schedule_id'));

                $tipoId = $row->std_tub_tipo_id ? (int)$row->std_tub_tipo_id : null;
                $materialId = $row->std_tub_material_id ? (int)$row->std_tub_material_id : null;

                $options = $this->optsSchedule($tipoId, $materialId);
                $optHtml = $this->optionHtml($options, (int)($row->std_tub_schedule_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateTubCell({$row->item_id}, 'std_tub_schedule_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('extremidade_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[3] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_tub_extremidade_id'));

                $tipoId = $row->std_tub_tipo_id ? (int)$row->std_tub_tipo_id : null;
                $materialId = $row->std_tub_material_id ? (int)$row->std_tub_material_id : null;
                $scheduleId = $row->std_tub_schedule_id ? (int)$row->std_tub_schedule_id : null;

                $options = $this->optsExtremidade($tipoId, $materialId, $scheduleId);
                $optHtml = $this->optionHtml($options, (int)($row->std_tub_extremidade_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateTubCell({$row->item_id}, 'std_tub_extremidade_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('diametro_edit', function ($row) {
                $p = $this->parseProb($row->prob);
                $prob = $p[4] ?? 100;
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_tub_diametro_id'));

                $tipoId = $row->std_tub_tipo_id ? (int)$row->std_tub_tipo_id : null;
                $materialId = $row->std_tub_material_id ? (int)$row->std_tub_material_id : null;
                $scheduleId = $row->std_tub_schedule_id ? (int)$row->std_tub_schedule_id : null;
                $extremidadeId = $row->std_tub_extremidade_id ? (int)$row->std_tub_extremidade_id : null;

                $options = $this->optsDiametro($tipoId, $materialId, $scheduleId, $extremidadeId);
                $optHtml = $this->optionHtml($options, (int)($row->std_tub_diametro_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateTubCell({$row->item_id}, 'std_tub_diametro_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('hh_un', fn($row) => $row->hh_un ?? '')
            ->add('kg_hh', fn($row) => $row->kg_hh ?? '')
            ->add('kg_un', fn($row) => $row->kg_un ?? '')
            ->add('m2_un', fn($row) => $row->m2_un ?? '')

            ->add('status_badge', function ($row) {
                $st = (string)($row->sample_status ?? '');
                $cls = $st === MlFeedbackSample::STATUS_APROVADO ? 'badge badge-success' : 'badge badge-danger';
                return new HtmlString("<span class=\"{$cls}\">{$st}</span>");
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Descrição', 'descricao')->searchable(),

            Column::make('TIPO', 'tipo_edit'),
            Column::make('MATERIAL', 'material_edit'),
            Column::make('SCHEDULE', 'schedule_edit'),
            Column::make('EXTREMIDADE', 'extremidade_edit'),
            Column::make('DIÂMETRO', 'diametro_edit'),

            Column::make('HH/UN', 'hh_un'),
            Column::make('KG/HH', 'kg_hh'),
            Column::make('KG/UN', 'kg_un'),
            Column::make('M2/UN', 'm2_un'),

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
                ->dispatch('toggleShowApprovedTUB', []),

            Button::add('approve_all')
                ->slot('Aprovar Tudo da Página')
                ->class('btn btn-sm btn-success')
                ->dispatch('approveAllPageTUB', []),
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
                ->dispatch('approveSampleTUB', [$row->id]),

            Button::add('reject')
                ->slot('Reprovar')
                ->class('btn btn-sm btn-danger')
                ->dispatch('rejectSampleTUB', [$row->id]),
        ];
    }

    /**
     * ✅ Aprova tudo da página atual (respeitando filtros + paginação).
     */
    public function approveAllPage(): void
    {
        if ($this->showApproved ?? false) {
            return;
        }

        [$page, $perPage] = $this->currentPagination();

        $q = $this->baseApprovalQueryTUB();

        $ids = (clone $q)
            ->forPage($page, $perPage)
            ->pluck('s.id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->toArray();

        if (empty($ids)) {
            return;
        }

        MlFeedbackSample::query()
            ->whereIn('id', $ids)
            ->update([
                'status'     => MlFeedbackSample::STATUS_APROVADO,
                'updated_at' => now(),
            ]);

        $this->dispatch('reloadPowergrid');
    }

    /**
     * ✅ Query base da grid TUB (mesma do datasource + filtros extras).
     */
    private function baseApprovalQueryTUB(): Builder
    {
        $q = MlFeedbackSample::query()
            ->from('ml_feedback_samples as s')
            ->join('orc_ml_std_itens as i', 'i.id', '=', 's.orc_ml_std_item_id')
            ->where('s.disciplina', 'TUB');

        if (!($this->showApproved ?? false)) {
            $q->where('s.status', MlFeedbackSample::STATUS_NAO_REVISADO);
        }

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

        return $q->orderBy('s.updated_at', 'desc')
                 ->orderBy('s.id', 'asc');
    }

    /**
     * ✅ Captura valor de filtros do PowerGrid (tolerante a variações de versão).
     */
    private function pgFilterValue(array $possibleKeys)
    {
        $filters = $this->filters ?? null;

        if (is_array($filters)) {
            foreach ($possibleKeys as $k) {
                if (!array_key_exists($k, $filters)) continue;

                $f = $filters[$k];

                if (is_array($f)) {
                    if (array_key_exists('value', $f)) return $f['value'];
                    if (array_key_exists('search', $f)) return $f['search'];
                }

                if (is_object($f)) {
                    if (property_exists($f, 'value')) return $f->value;
                    if (property_exists($f, 'search')) return $f->search;
                }
            }
        }

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
        $page = (int) ($this->page ?? 1);

        $perPage = $this->perPage ?? null;
        if (!$perPage && property_exists($this, 'perPageValue')) {
            $perPage = $this->perPageValue;
        }
        if (!$perPage) {
            $perPage = 10;
        }

        return [$page, (int) $perPage];
    }
}
