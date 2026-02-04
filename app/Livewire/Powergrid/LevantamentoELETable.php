<?php

namespace App\Livewire\Powergrid;

use App\Helpers\PowerGridThemes\TailwindHeaderFixed;
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

final class LevantamentoELETable extends PowerGridComponent
{
    protected $listeners = [
        'reloadPowergrid',
        'toggleFullscreenEle',
        'closeFullscreenEle',
        'closeDescricao',
    ];

    public int $orcMLstdId;

    public string $tableName = 'levantamento-ele-table';

    public bool $isFullscreen = false;

    /** =========================================================
     *  ✅ CACHE: OPTIONS (para não consultar DB a cada render)
     *  ========================================================= */
    private ?array $cacheOptsTipo = null;
    private array $cacheOptsMaterialByTipo = [];        // [tipoId|null => array]
    private array $cacheOptsConexaoByKey = [];          // ["tipo|material" => array]
    private array $cacheOptsEspessuraByKey = [];        // ["tipo|material|conexao" => array]
    private array $cacheOptsExtremidadeByKey = [];      // ["tipo|material|conexao|espessura" => array]
    private array $cacheOptsDimensaoByKey = [];         // ["tipo|material|conexao|espessura|extremidade" => array]

    /** =========================================================
     *  ✅ CACHE: STD (combinação -> std)
     *  ========================================================= */
    private array $cacheStdByKey = [];

    /** =========================================================
     *  ✅ CACHE: ID -> NOME (para feedback sem N+1)
     *  ========================================================= */
    private ?array $nameTipoById = null;
    private ?array $nameMaterialById = null;
    private ?array $nameConexaoById = null;
    private ?array $nameEspessuraById = null;
    private ?array $nameExtremidadeById = null;
    private ?array $nameDimensaoById = null;

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
            ->where('disciplina', 'ELE')
            ->orderBy('ordem');
    }

    public function relationSearch(): array
    {
        return [];
    }

    // =========================================================
    // PROB helpers (ELE: 6 itens)
    // ordem: tipo/material/conexao/espessura/extremidade/dimensao
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

        // pluck('nome','id') => [id => nome]
        $this->nameTipoById        = StdEleTipo::query()->pluck('nome', 'id')->toArray();
        $this->nameMaterialById    = StdEleMaterial::query()->pluck('nome', 'id')->toArray();
        $this->nameConexaoById     = StdEleConexao::query()->pluck('nome', 'id')->toArray();
        $this->nameEspessuraById   = StdEleEspessura::query()->pluck('nome', 'id')->toArray();
        $this->nameExtremidadeById = StdEleExtremidade::query()->pluck('nome', 'id')->toArray();
        $this->nameDimensaoById    = StdEleDimensao::query()->pluck('nome', 'id')->toArray();
    }

    // =========================================================
    // OPTIONS CASCADE (AGORA COM CACHE)
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

    // =========================================================
    // ✅ PONTO CERTO DA CAPTURA DA EDIÇÃO DO USUÁRIO (ELE)
    // =========================================================
    public function updateEleCell(int $rowId, string $field, $value): void
    {
        $value = $value === '' ? null : (int) $value;

        /** @var OrcMLstdItem|null $item */
        $item = OrcMLstdItem::query()->find($rowId);
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

        // auto-preenche filhos (somente se usuário não limpou)
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

        // registra edit do user SOMENTE no campo alterado
        if (Schema::hasColumn('orc_ml_std_itens', 'user_edits')) {
            $edits = $this->getUserEdits($item);

            if (!in_array($field, $edits, true)) {
                $edits[] = $field;
            }

            $item->user_edits = json_encode(array_values($edits));
        }

        // ✅ recalcula STD (com cache)
        $item->std_ele = $this->resolveStdCached($item);

        $item->save();

        // ✅ feedback user edit sem N+1
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
                $cls = $this->classByProb($prob, $this->wasEdited($row, 'std_ele_tipo_id'));

                $options = $this->optsTipo();
                $optHtml = $this->optionHtml($options, (int)($row->std_ele_tipo_id ?? 0));

                return new HtmlString("
                    <select class='form-control form-control-sm {$cls}'
                        wire:change=\"updateEleCell({$row->id}, 'std_ele_tipo_id', \$event.target.value)\">
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
                        wire:change=\"updateEleCell({$row->id}, 'std_ele_material_id', \$event.target.value)\">
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
                        wire:change=\"updateEleCell({$row->id}, 'std_ele_conexao_id', \$event.target.value)\">
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
                        wire:change=\"updateEleCell({$row->id}, 'std_ele_espessura_id', \$event.target.value)\">
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
                        wire:change=\"updateEleCell({$row->id}, 'std_ele_extremidade_id', \$event.target.value)\">
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
                        wire:change=\"updateEleCell({$row->id}, 'std_ele_dimensao_id', \$event.target.value)\">
                        {$optHtml}
                    </select>
                ");
            })

            ->add('prob', fn($row) => $row->prob ?? '')
            ->add('std', function ($row) {
                    if ($row->std_ele === null || $row->std_ele === '') return '';
                    return number_format((float) $row->std_ele, 2, ',', '.');
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
            Column::make('CONEXÃO', 'conexao_edit'),
            Column::make('ESPESSURA', 'espessura_edit'),
            Column::make('EXTREMIDADE', 'extremidade_edit'),
            Column::make('DIMENSÃO', 'dimensao_edit'),

            Column::make('%_PROB', 'prob'),
            Column::make('STD', 'std'),
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
            Button::add('ml_ele')
                ->slot('Executar ML (ELE)')
                ->class('btn btn-success')
                ->openModal('modal.orc-mlstd.run-ml', [
                    'orcMLstdId' => $this->orcMLstdId,
                    'disciplina' => 'ELE',
                ]),
            Button::add('fullscreen_ele')
                ->slot($this->isFullscreen ? 'Sair da tela cheia' : 'Tela cheia')
                ->class('btn btn-secondary')
                ->dispatch('toggleFullscreenEle', []), // dispara o listener acima
        ];
    }
    public function filters(): array
    {
        return [
            Filter::inputText('descricao')
                ->placeholder('Descrição'),
        ];
    }

    public function toggleFullscreenEle(): void
    {
        $this->isFullscreen = !$this->isFullscreen;

        $this->dispatch('pg-toggle-fullscreen', table: $this->tableName, on: $this->isFullscreen);
    }
    public function closeFullscreenEle(): void
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
