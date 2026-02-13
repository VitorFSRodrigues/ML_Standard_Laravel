<?php

namespace App\Modules\MLStandard\Livewire\Powergrid;

use App\Helpers\PowerGridThemes\TailwindHeaderFixed;
use App\Modules\MLStandard\Models\OrcMLstdItem;

use App\Modules\MLStandard\Models\StdTUB;
use App\Modules\MLStandard\Models\StdTubTipo;
use App\Modules\MLStandard\Models\StdTubMaterial;
use App\Modules\MLStandard\Models\StdTubSchedule;
use App\Modules\MLStandard\Models\StdTubExtremidade;
use App\Modules\MLStandard\Models\StdTubDiametro;

use App\Services\MlFeedbackService;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\Rule;

final class LevantamentoTUBTable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
        'toggleFullscreenTub',
        'closeFullscreenTub',
        'closeDescricao',
    ];

    public int $orcMLstdId;

    public string $tableName = 'levantamento-tub-table';

    public bool $isFullscreen = false;

    /** =========================================================
     *  ✅ CACHE: OPTIONS
     *  ========================================================= */
    private ?array $cacheOptsTipo = null;
    private array $cacheOptsMaterialByTipo = [];        // [tipoId|null => array]
    private array $cacheOptsScheduleByKey = [];         // ["tipo|material" => array]
    private array $cacheOptsExtremidadeByKey = [];      // ["tipo|material|schedule" => array]
    private array $cacheOptsDiametroByKey = [];         // ["tipo|material|schedule|extremidade" => array]

    /** =========================================================
     *  ✅ CACHE: Valores STD_TUB (combinação -> [hh_un,kg_hh,kg_un,m2_un])
     *  ========================================================= */
    private array $cacheTubValsByKey = [];

    /** =========================================================
     *  ✅ CACHE: ID -> NOME (feedback sem N+1)
     *  ========================================================= */
    private ?array $nameTipoById = null;
    private ?array $nameMaterialById = null;
    private ?array $nameScheduleById = null;
    private ?array $nameExtremidadeById = null;
    private ?array $nameDiametroById = null;

    public function reloadPowergrid(): void
    {
        $this->refresh();
    }

    public function customThemeClass(): ?string
    {
        return TailwindHeaderFixed::class;
    }
    
    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput(),
            // PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return OrcMLstdItem::query()
            ->where('orc_ml_std_id', $this->orcMLstdId)
            ->where('disciplina', 'TUB')
            ->orderBy('ordem');
    }

    public function relationSearch(): array
    {
        return [];
    }

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
    // ✅ Warmup ID->Nome
    // =========================================================
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

    /** =========================================================
     *  ✅ Cache valores da combinação TUB
     *  ========================================================= */
    private function tubKey(int $tipo, int $material, int $schedule, int $extremidade, int $diametro): string
    {
        return implode('|', [$tipo, $material, $schedule, $extremidade, $diametro]);
    }

    private function resolveTubValsCached(OrcMLstdItem $item): array
    {
        // default nulls
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
    // ✅ PONTO CERTO DA CAPTURA DA EDIÇÃO DO USUÁRIO (TUB)
    // =========================================================
    public function updateTubCell(int $rowId, string $field, $value): void
    {
        $value = $value === '' ? null : (int) $value;

        /** @var OrcMLstdItem|null $item */
        $item = OrcMLstdItem::query()->find($rowId);
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

        // auto-preenche filhos
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

        // registra user edit
        if (Schema::hasColumn('orc_ml_std_itens', 'user_edits')) {
            $edits = $this->getUserEdits($item);

            if (!in_array($field, $edits, true)) {
                $edits[] = $field;
            }

            $item->user_edits = json_encode(array_values($edits));
        }

        // ✅ recalcula valores TUB com cache
        [$hh_un, $kg_hh, $kg_un, $m2_un] = $this->resolveTubValsCached($item);

        $item->hh_un = $hh_un;
        $item->kg_hh = $kg_hh;
        $item->kg_un = $kg_un;
        $item->m2_un = $m2_un;

        $item->save();

        // ✅ feedback user edit sem N+1
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

    public function updateIgnorarDesc(int $rowId, $value): void
    {
        /** @var OrcMLstdItem|null $item */
        $item = OrcMLstdItem::query()->find($rowId);
        if (!$item) return;

        $checked = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $item->ignorar_desc = $checked ?? false;
        $item->save();

        $this->dispatch('reloadPowergrid');
    }

    // =========================================================
    // FIELDS + COLUMNS
    // =========================================================
    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')

            ->add('descricao', function ($row) {
                $full  = (string)($row->descricao ?? '');
                $short = Str::limit($full, 140, '...');

                $isOpen = ($this->expandedDescId === (int)$row->id);

                if ($isOpen) {
                    return new HtmlString(
                        '<div style="max-width:520px;">'
                        . '<textarea readonly onclick="this.select()" '
                        . 'class="form-control form-control-sm" '
                        . 'style="height:120px;white-space:pre-wrap;cursor:text;">'
                        . e($full)
                        . '</textarea>'
                        . '<div class="mt-1 d-flex gap-2">'
                        . '  <button type="button" class="btn btn-xs btn-outline-secondary" '
                        . '          wire:click="closeDescricao">Fechar</button>'
                        . '</div>'
                        . '</div>'
                    );
                }

                return new HtmlString(
                    '<div '
                    . 'title="' . e($full) . '" '
                    . 'style="white-space:normal;word-break:break-word;max-width:520px;cursor:pointer;" '
                    . 'wire:click="toggleDescricao(' . (int)$row->id . ')">'
                    . e($short)
                    . '</div>'
                );
            })

            ->add('ignorar_desc_checkbox', function ($row) {
                $checked = $row->ignorar_desc ? 'checked' : '';

                return new HtmlString(
                    "<input type=\"checkbox\" class=\"form-check-input\" {$checked} "
                    . "aria-label=\"Ignorar linha\" "
                    . "wire:change=\"updateIgnorarDesc({$row->id}, \$event.target.checked)\">"
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
                        wire:change=\"updateTubCell({$row->id}, 'std_tub_tipo_id', \$event.target.value)\">
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
                        wire:change=\"updateTubCell({$row->id}, 'std_tub_material_id', \$event.target.value)\">
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
                        wire:change=\"updateTubCell({$row->id}, 'std_tub_schedule_id', \$event.target.value)\">
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
                        wire:change=\"updateTubCell({$row->id}, 'std_tub_extremidade_id', \$event.target.value)\">
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
                        wire:change=\"updateTubCell({$row->id}, 'std_tub_diametro_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('prob', fn($row) => $row->prob ?? '')
            ->add('hh_un', function ($row) {
                    if ($row->hh_un === null || $row->hh_un === '') return '';
                    return number_format((float) $row->hh_un, 2, ',', '.');
                })
            ->add('kg_hh', function ($row) {
                    if ($row->kg_hh === null || $row->kg_hh === '') return '';
                    return number_format((float) $row->kg_hh, 2, ',', '.');
                })
            ->add('kg_un', function ($row) {
                    if ($row->kg_un === null || $row->kg_un === '') return '';
                    return number_format((float) $row->kg_un, 2, ',', '.');
                })
            ->add('m2_un', function ($row) {
                    if ($row->m2_un === null || $row->m2_un === '') return '';
                    return number_format((float) $row->m2_un, 2, ',', '.');
                });
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make('Ignorar linha', 'ignorar_desc_checkbox'),
            Column::make('Descrição', 'descricao')->searchable(),

            Column::make('TIPO', 'tipo_edit'),
            Column::make('MATERIAL', 'material_edit'),
            Column::make('SCHEDULE', 'schedule_edit'),
            Column::make('EXTREMIDADE', 'extremidade_edit'),
            Column::make('DIÂMETRO', 'diametro_edit'),

            Column::make('%_PROB', 'prob'),
            Column::make('HH/UN', 'hh_un'),
            Column::make('KG/HH', 'kg_hh'),
            Column::make('KG/UN', 'kg_un'),
            Column::make('M2/UN', 'm2_un'),
        ];
    }

    public function actionRules($row): array
    {
        return [
            Rule::rows()
                ->when(fn ($row) => (bool) $row->ignorar_desc)
                ->setAttribute('style', 'background-color: #f8d7da;'),
        ];
    }

    public function header(): array
    {
        return [
            Button::add('ml_tub')
                ->slot('Executar ML (TUB)')
                ->class('btn btn-success')
                ->openModal('modal.orc-mlstd.run-ml', [
                    'orcMLstdId' => $this->orcMLstdId,
                    'disciplina' => 'TUB',
                ]),
            Button::add('fullscreen_tub')
                ->slot($this->isFullscreen ? 'Sair da tela cheia' : 'Tela cheia')
                ->class('btn btn-secondary')
                ->dispatch('toggleFullscreenTub', []), // dispara o listener acima
        ];
    }
    public function filters(): array
    {
        return [
            Filter::inputText('descricao')
                ->placeholder('Descrição'),
        ];
    }

    public function toggleFullscreenTub(): void
    {
        $this->isFullscreen = !$this->isFullscreen;

        if ($this->isFullscreen) {
            $this->expandedDescId = null;
        }

        $this->dispatch('pg-toggle-fullscreen', table: $this->tableName, on: $this->isFullscreen);
    }

    public function closeFullscreenTub(): void
    {
        $this->isFullscreen = false;
        $this->dispatch('pg-toggle-fullscreen', table: $this->tableName, on: false);
    }

    public ?int $expandedDescId = null;

    public function toggleDescricao(int $rowId): void
    {
        $this->expandedDescId = ($this->expandedDescId === $rowId) ? null : $rowId;
    }

    public function closeDescricao(): void
    {
        $this->expandedDescId = null;
    }

}