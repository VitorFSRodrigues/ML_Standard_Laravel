<?php

namespace App\Livewire\Powergrid;

use App\Models\DictEleConexao;
use App\Models\DictEleDimensao;
use App\Models\DictEleEspessura;
use App\Models\DictEleExtremidade;
use App\Models\DictEleMaterial;
use App\Models\DictEleTipo;
use App\Models\MlFeedbackSample;
use App\Models\Varredura;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Facades\Rule;

final class VarreduraELETable extends PowerGridComponent
{
    public string $tableName = 'varredura-ele-table';
    public ?int $varreduraId = null;
    public bool $isFullscreen = false;

    protected $listeners = [
        'reloadPowergrid',
        'refazerVarreduraELE' => 'refazerVarredura',
        'toggleFullscreenEle',
        'closeFullscreenEle',
    ];

    private ?Varredura $currentVarredura = null;
    private ?array $dictCache = null;
    private array $scanCache = [];

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

    public function datasource(): Builder|Collection
    {
        $query = MlFeedbackSample::query()
            ->from('ml_feedback_samples as s')
            ->select([
                's.id',
                's.disciplina',
                's.status',
                's.descricao_original',
                's.user_final_json',
                's.ml_pred_json',
            ])
            ->where('s.disciplina', 'ELE')
            ->where('s.status', MlFeedbackSample::STATUS_APROVADO);

        $rows = $query->orderBy('s.id')->get();

        return $rows->filter(fn ($row) => $this->rowHasEncFalse($row))->values();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('descricao', function ($row) {
                $full = (string) ($row->descricao_original ?? '');

                return new HtmlString(
                    '<div title="' . e($full) . '" style="white-space:normal;word-break:break-word;width:420px;max-width:420px;">'
                    . e($full) .
                    '</div>'
                );
            })
            ->add('tipo_user_prev', fn ($row) => $this->wrapCell($this->scanRow($row)['tipo_user_prev'] ?? ''))
            ->add('material_user_prev', fn ($row) => $this->wrapCell($this->scanRow($row)['material_user_prev'] ?? ''))
            ->add('conexao_user_prev', fn ($row) => $this->wrapCell($this->scanRow($row)['conexao_user_prev'] ?? ''))
            ->add('espessura_user_prev', fn ($row) => $this->wrapCell($this->scanRow($row)['espessura_user_prev'] ?? ''))
            ->add('extremidade_user_prev', fn ($row) => $this->wrapCell($this->scanRow($row)['extremidade_user_prev'] ?? ''))
            ->add('dimensao_user_prev', fn ($row) => $this->wrapCell($this->scanRow($row)['dimensao_user_prev'] ?? ''))

            ->add('tipo_dict', fn ($row) => $this->wrapCell($this->formatDict($this->scanRow($row)['tipo_dict'] ?? [])))
            ->add('material_dict', fn ($row) => $this->wrapCell($this->formatDict($this->scanRow($row)['material_dict'] ?? [])))
            ->add('conexao_dict', fn ($row) => $this->wrapCell($this->formatDict($this->scanRow($row)['conexao_dict'] ?? [])))
            ->add('espessura_dict', fn ($row) => $this->wrapCell($this->formatDict($this->scanRow($row)['espessura_dict'] ?? [])))
            ->add('extremidade_dict', fn ($row) => $this->wrapCell($this->formatDict($this->scanRow($row)['extremidade_dict'] ?? [])))
            ->add('dimensao_dict', fn ($row) => $this->wrapCell($this->formatDict($this->scanRow($row)['dimensao_dict'] ?? [])))

            ->add('tipo_enc', fn ($row) => $this->formatBool($this->scanRow($row)['tipo_enc'] ?? false))
            ->add('material_enc', fn ($row) => $this->formatBool($this->scanRow($row)['material_enc'] ?? false))
            ->add('conexao_enc', fn ($row) => $this->formatBool($this->scanRow($row)['conexao_enc'] ?? false))
            ->add('espessura_enc', fn ($row) => $this->formatBool($this->scanRow($row)['espessura_enc'] ?? false))
            ->add('extremidade_enc', fn ($row) => $this->formatBool($this->scanRow($row)['extremidade_enc'] ?? false))
            ->add('dimensao_enc', fn ($row) => $this->formatBool($this->scanRow($row)['dimensao_enc'] ?? false));
    }

    public function columns(): array
    {
        return [
            Column::make('Descrição', 'descricao')->searchable(),

            Column::make('Tip.user_prev', 'tipo_user_prev'),
            Column::make('Mat.user_prev', 'material_user_prev'),
            Column::make('Conex.user_prev', 'conexao_user_prev'),
            Column::make('Esp.user_prev', 'espessura_user_prev'),
            Column::make('Ext.user_prev', 'extremidade_user_prev'),
            Column::make('Dim.user_prev', 'dimensao_user_prev'),

            Column::make('Tip.dict', 'tipo_dict'),
            Column::make('Mat.dict', 'material_dict'),
            Column::make('Conex.dict', 'conexao_dict'),
            Column::make('Esp.dict', 'espessura_dict'),
            Column::make('Ext.dict', 'extremidade_dict'),
            Column::make('Dim.dict', 'dimensao_dict'),

            Column::make('Tip.enc', 'tipo_enc'),
            Column::make('Mat.enc', 'material_enc'),
            Column::make('Conex.enc', 'conexao_enc'),
            Column::make('Esp.enc', 'espessura_enc'),
            Column::make('Ext.enc', 'extremidade_enc'),
            Column::make('Dim.enc', 'dimensao_enc'),
        ];
    }

    public function header(): array
    {
        if ($this->isReady()) {
            return [];
        }

        return [
            Button::add('add_dict_ele')
                ->slot('Adicionar Item Dicionário')
                ->class('btn btn-outline-primary')
                ->openModal('modal.varredura.add-dict-item', [
                    'disciplina' => 'ELE',
                    'varreduraId' => $this->currentVarreduraId(),
                ]),
            Button::add('refazer_varredura_ele')
                ->slot('Refazer varredura')
                ->class('btn btn-outline-secondary')
                ->dispatch('refazerVarreduraELE', []),
            Button::add('pronto_ele')
                ->slot('Pronto para treino')
                ->class('btn btn-success')
                ->openModal('modal.varredura.confirm-ready', [
                    'disciplina' => 'ELE',
                    'varreduraId' => $this->currentVarreduraId(),
                ]),
            Button::add('fullscreen_ele')
                ->slot($this->isFullscreen ? 'Sair da tela cheia' : 'Tela cheia')
                ->class('btn btn-secondary')
                ->dispatch('toggleFullscreenEle', []),
        ];
    }

    public function actionRules($row): array
    {
        return [
            Rule::rows()
                ->when(fn ($row) => $this->rowHasEncFalse($row))
                ->setAttribute('style', 'background-color: #fff3cd;'),
        ];
    }

    public function refazerVarredura(): void
    {
        $this->dictCache = null;
        $this->scanCache = [];
        $this->reloadPowergrid();
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

    private function isReady(): bool
    {
        $current = $this->currentVarredura();
        return $current ? (bool) $current->status_ele : false;
    }

    private function currentVarredura(): ?Varredura
    {
        if ($this->currentVarredura !== null) {
            return $this->currentVarredura;
        }

        if ($this->varreduraId) {
            return $this->currentVarredura = Varredura::query()->find($this->varreduraId);
        }

        return $this->currentVarredura = Varredura::query()->orderByDesc('id')->first();
    }

    private function currentVarreduraId(): ?int
    {
        $current = $this->currentVarredura();
        return $current?->id;
    }

    private function currentRevision(): int
    {
        $current = $this->currentVarredura();
        return $current ? (int) $current->revisao_ele : 0;
    }

    private function dictEntries(): array
    {
        if ($this->dictCache !== null) {
            return $this->dictCache;
        }

        $this->dictCache = [
            'tipo' => DictEleTipo::query()->get(['Termo', 'Descricao_Padrao'])->toArray(),
            'material' => DictEleMaterial::query()->get(['Termo', 'Descricao_Padrao'])->toArray(),
            'conexao' => DictEleConexao::query()->get(['Termo', 'Descricao_Padrao'])->toArray(),
            'espessura' => DictEleEspessura::query()->get(['Termo', 'Descricao_Padrao'])->toArray(),
            'extremidade' => DictEleExtremidade::query()->get(['Termo', 'Descricao_Padrao'])->toArray(),
            'dimensao' => DictEleDimensao::query()->get(['Termo', 'Descricao_Padrao'])->toArray(),
        ];

        return $this->dictCache;
    }

    private function scanRow($row): array
    {
        $rowId = (int) ($row->id ?? 0);
        if ($rowId && isset($this->scanCache[$rowId])) {
            return $this->scanCache[$rowId];
        }

        $descricao = (string) ($row->descricao_original ?? '');
        $descricaoNorm = $this->normalize($descricao);

        $dicts = $this->dictEntries();

        $result = [];
        $anyFalse = false;

        foreach (['tipo', 'material', 'conexao', 'espessura', 'extremidade', 'dimensao'] as $key) {
            $list = $this->scanDict($descricaoNorm, $dicts[$key] ?? []);
            $userPrev = $this->userPrevValue($row, $key);
            $enc = $this->isUserPrevInDict($userPrev, $list);

            $result["{$key}_user_prev"] = $userPrev ?? '';
            $result["{$key}_dict"] = $list;
            $result["{$key}_enc"] = $enc;

            if (!$enc) {
                $anyFalse = true;
            }
        }

        $result['any_enc_false'] = $anyFalse;

        if ($rowId) {
            $this->scanCache[$rowId] = $result;
        }

        return $result;
    }

    private function rowHasEncFalse($row): bool
    {
        return (bool) ($this->scanRow($row)['any_enc_false'] ?? false);
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        return mb_strtoupper($value, 'UTF-8');
    }

    private function scanDict(string $descricaoNorm, array $dictRows): array
    {
        $matches = [];

        foreach ($dictRows as $row) {
            $term = $this->normalize((string) ($row['Termo'] ?? ''));
            if ($term === '') {
                continue;
            }

            if (mb_stripos($descricaoNorm, $term, 0, 'UTF-8') !== false) {
                $matches[] = (string) ($row['Descricao_Padrao'] ?? '');
            }
        }

        $matches = array_filter($matches, fn ($v) => trim((string) $v) !== '');
        return array_values(array_unique($matches));
    }

    private function userPrevValue($row, string $key): ?string
    {
        $userFinal = $this->decodeJson($row->user_final_json ?? null);
        if (is_array($userFinal) && array_key_exists($key, $userFinal)) {
            return $this->normalize((string) $userFinal[$key]);
        }

        $mlPred = $this->decodeJson($row->ml_pred_json ?? null);
        if (is_array($mlPred) && array_key_exists($key, $mlPred)) {
            return $this->normalize((string) $mlPred[$key]);
        }

        return null;
    }

    private function decodeJson($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function isUserPrevInDict(?string $userPrev, array $dictList): bool
    {
        if (!$userPrev) {
            return false;
        }

        $userPrevNorm = $this->normalize($userPrev);
        $dictNorm = array_map(fn ($v) => $this->normalize((string) $v), $dictList);

        return in_array($userPrevNorm, $dictNorm, true);
    }

    private function formatDict(array $values): string
    {
        if (empty($values)) {
            return '';
        }

        return implode('; ', $values);
    }

    private function formatBool(bool $value): HtmlString
    {
        $cls = $value ? 'badge badge-success' : 'badge badge-danger';
        $label = $value ? 'SIM' : 'NÃO';
        return new HtmlString("<span class=\"{$cls}\">{$label}</span>");
    }

    private function wrapCell(string $value, int $maxWidth = 180): HtmlString
    {
        $text = trim($value);
        return new HtmlString(
            '<div style="white-space:normal;word-break:break-word;max-width:' . $maxWidth . 'px;">'
            . e($text) .
            '</div>'
        );
    }
}
