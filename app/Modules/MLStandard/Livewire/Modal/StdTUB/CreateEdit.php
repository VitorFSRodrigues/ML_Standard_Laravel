<?php

namespace App\Modules\MLStandard\Livewire\Modal\StdTUB;

use App\Modules\MLStandard\Models\StdTUB;
use App\Modules\MLStandard\Models\StdTubTipo;
use App\Modules\MLStandard\Models\StdTubMaterial;
use App\Modules\MLStandard\Models\StdTubSchedule;
use App\Modules\MLStandard\Models\StdTubExtremidade;
use App\Modules\MLStandard\Models\StdTubDiametro;
use LivewireUI\Modal\ModalComponent;

class CreateEdit extends ModalComponent
{
    public int|string|null $stdTUBId = null;

    // ✅ listas para datalist (sugestões)
    public array $tipos = [];
    public array $materiais = [];
    public array $schedules = [];
    public array $extremidades = [];
    public array $diametros = [];

    public array $form = [
        // ✅ agora são nomes (texto corrido)
        'tipo_nome'        => '',
        'material_nome'    => '',
        'schedule_nome'    => '',
        'extremidade_nome' => '',
        'diametro_nome'    => '',

        // STD principal
        'hh_un' => 0,
        'kg_hh' => 0,
        'kg_un' => 0,
        'm2_un' => 0,

        // percentuais
        'encarregado_mecanica' => 0,
        'encarregado_tubulacao' => 0,
        'encarregado_eletrica' => 0,
        'encarregado_andaime' => 0,
        'encarregado_civil' => 0,

        'lider' => 0,

        'mecanico_ajustador' => 0,
        'mecanico_montador' => 0,
        'encanador' => 0,
        'caldeireiro' => 0,
        'lixador' => 0,
        'montador' => 0,

        'soldador_er' => 0,
        'soldador_tig' => 0,
        'soldador_mig' => 0,

        'ponteador' => 0,

        'eletricista_controlista' => 0,
        'eletricista_montador' => 0,
        'instrumentista' => 0,

        'montador_de_andaime' => 0,
        'pintor' => 0,
        'jatista' => 0,
        'pedreiro' => 0,
        'carpinteiro' => 0,
        'armador' => 0,
        'ajudante' => 0,
    ];

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int|string|null $stdTUBId = null): void
    {
        $this->stdTUBId = $stdTUBId ? (int) $stdTUBId : null;

        // ✅ sugestões (datalist)
        $this->tipos        = StdTubTipo::query()->orderBy('nome')->pluck('nome')->toArray();
        $this->materiais    = StdTubMaterial::query()->orderBy('nome')->pluck('nome')->toArray();
        $this->schedules    = StdTubSchedule::query()->orderBy('nome')->pluck('nome')->toArray();
        $this->extremidades = StdTubExtremidade::query()->orderBy('nome')->pluck('nome')->toArray();
        $this->diametros    = StdTubDiametro::query()->orderBy('nome')->pluck('nome')->toArray();

        // edição
        if ($this->stdTUBId) {
            $row = StdTUB::with(['tipo', 'material', 'schedule', 'extremidade', 'diametro'])
                ->findOrFail($this->stdTUBId);

            $this->form = array_merge($this->form, [
                'tipo_nome'        => $row->tipo?->nome ?? '',
                'material_nome'    => $row->material?->nome ?? '',
                'schedule_nome'    => $row->schedule?->nome ?? '',
                'extremidade_nome' => $row->extremidade?->nome ?? '',
                'diametro_nome'    => $row->diametro?->nome ?? '',

                'hh_un' => $row->hh_un,
                'kg_hh' => $row->kg_hh,
                'kg_un' => $row->kg_un,
                'm2_un' => $row->m2_un,

                // percentuais (já estão em 0-1 no banco)
                'encarregado_mecanica' => $row->encarregado_mecanica,
                'encarregado_tubulacao' => $row->encarregado_tubulacao,
                'encarregado_eletrica' => $row->encarregado_eletrica,
                'encarregado_andaime' => $row->encarregado_andaime,
                'encarregado_civil' => $row->encarregado_civil,

                'lider' => $row->lider,

                'mecanico_ajustador' => $row->mecanico_ajustador,
                'mecanico_montador' => $row->mecanico_montador,
                'encanador' => $row->encanador,
                'caldeireiro' => $row->caldeireiro,
                'lixador' => $row->lixador,
                'montador' => $row->montador,

                'soldador_er' => $row->soldador_er,
                'soldador_tig' => $row->soldador_tig,
                'soldador_mig' => $row->soldador_mig,

                'ponteador' => $row->ponteador,

                'eletricista_controlista' => $row->eletricista_controlista,
                'eletricista_montador' => $row->eletricista_montador,
                'instrumentista' => $row->instrumentista,

                'montador_de_andaime' => $row->montador_de_andaime,
                'pintor' => $row->pintor,
                'jatista' => $row->jatista,
                'pedreiro' => $row->pedreiro,
                'carpinteiro' => $row->carpinteiro,
                'armador' => $row->armador,
                'ajudante' => $row->ajudante,
            ]);
        }
    }

    /**
     * Campos percentuais que devem somar 100%.
     */
    private function percentFields(): array
    {
        return [
            'encarregado_mecanica','encarregado_tubulacao','encarregado_eletrica','encarregado_andaime','encarregado_civil',
            'lider',
            'mecanico_ajustador','mecanico_montador','encanador','caldeireiro','lixador','montador',
            'soldador_er','soldador_tig','soldador_mig',
            'ponteador',
            'eletricista_controlista','eletricista_montador','instrumentista',
            'montador_de_andaime','pintor','jatista','pedreiro','carpinteiro','armador','ajudante',
        ];
    }

    /**
     * Normaliza percentual:
     * "10,05%" -> 0.1005
     * "10,05"  -> 0.1005
     * "0,1005" -> 0.1005
     */
    private function normalizePercent($value): float
    {
        if ($value === null || $value === '') return 0.0;

        $raw = trim((string) $value);
        $hasPercent = str_contains($raw, '%');

        $raw = str_replace(['%', ' '], '', $raw);

        // BR: 10,05 -> 10.05
        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        $num = is_numeric($raw) ? (float) $raw : 0.0;

        // 10 ou 10% => divide por 100
        if ($hasPercent || $num > 1) {
            $num = $num / 100;
        }

        if ($num < 0) $num = 0;
        if ($num > 1) $num = 1;

        return round($num, 6);
    }

    /**
     * ✅ Soma atual dos cargos (0..1)
     */
    public function getCargosSumProperty(): float
    {
        $sum = 0.0;

        foreach ($this->percentFields() as $f) {
            $sum += $this->normalizePercent($this->form[$f] ?? 0);
        }

        return $sum;
    }

    /**
     * ✅ Ex: "87,5%" ou "100,0%"
     */
    public function getCargosSumFmtProperty(): string
    {
        $pct = $this->cargosSum * 100;
        return number_format($pct, 1, ',', '.') . '%';
    }

    /**
     * ✅ true se ~100% (com tolerância)
     */
    public function getCargosSumOkProperty(): bool
    {
        return abs($this->cargosSum - 1.0) <= 0.0001;
    }

    private function normalizeUpper(?string $txt): string
    {
        $txt = trim((string) $txt);
        $txt = mb_strtoupper($txt, 'UTF-8');
        $txt = preg_replace('/\s+/', ' ', $txt);
        return $txt ?? '';
    }

    /**
     * ✅ Aceita:
     *  - "10,05%"  -> 0.1005
     *  - "10,05"   -> 0.1005
     *  - "0,1005"  -> 0.1005
     *  - "0.1005"  -> 0.1005
     */

    private function rules(): array
    {
        return [
            'form.tipo_nome'        => ['required', 'string', 'max:255'],
            'form.material_nome'    => ['required', 'string', 'max:255'],
            'form.schedule_nome'    => ['required', 'string', 'max:255'],
            'form.extremidade_nome' => ['required', 'string', 'max:255'],
            'form.diametro_nome'    => ['required', 'string', 'max:255'],

            'form.hh_un' => ['required', 'numeric', 'min:0'],
            'form.kg_hh' => ['required', 'numeric', 'min:0'],
            'form.kg_un' => ['required', 'numeric', 'min:0'],
            'form.m2_un' => ['required', 'numeric', 'min:0'],

            // percentuais aceitam texto -> normalizamos manualmente
            'form.encarregado_mecanica' => ['nullable'],
            'form.encarregado_tubulacao' => ['nullable'],
            'form.encarregado_eletrica' => ['nullable'],
            'form.encarregado_andaime' => ['nullable'],
            'form.encarregado_civil' => ['nullable'],
            'form.lider' => ['nullable'],

            'form.mecanico_ajustador' => ['nullable'],
            'form.mecanico_montador' => ['nullable'],
            'form.encanador' => ['nullable'],
            'form.caldeireiro' => ['nullable'],
            'form.lixador' => ['nullable'],
            'form.montador' => ['nullable'],

            'form.soldador_er' => ['nullable'],
            'form.soldador_tig' => ['nullable'],
            'form.soldador_mig' => ['nullable'],

            'form.ponteador' => ['nullable'],

            'form.eletricista_controlista' => ['nullable'],
            'form.eletricista_montador' => ['nullable'],
            'form.instrumentista' => ['nullable'],

            'form.montador_de_andaime' => ['nullable'],
            'form.pintor' => ['nullable'],
            'form.jatista' => ['nullable'],
            'form.pedreiro' => ['nullable'],
            'form.carpinteiro' => ['nullable'],
            'form.armador' => ['nullable'],
            'form.ajudante' => ['nullable'],
        ];
    }

    private function resolveOrCreate(string $modelClass, string $nome): int
    {
        $row = $modelClass::query()->firstOrCreate(['nome' => $nome]);
        return (int) $row->id;
    }

    public function save(): void
    {
        // ✅ normaliza nomes
        $this->form['tipo_nome']        = $this->normalizeUpper($this->form['tipo_nome'] ?? '');
        $this->form['material_nome']    = $this->normalizeUpper($this->form['material_nome'] ?? '');
        $this->form['schedule_nome']    = $this->normalizeUpper($this->form['schedule_nome'] ?? '');
        $this->form['extremidade_nome'] = $this->normalizeUpper($this->form['extremidade_nome'] ?? '');
        $this->form['diametro_nome']    = $this->normalizeUpper($this->form['diametro_nome'] ?? '');

        // ✅ normaliza percentuais (aceita 10,05%)
        foreach ($this->percentFields() as $f) {
            $this->form[$f] = $this->normalizePercent($this->form[$f] ?? 0);
        }

        $data = $this->validate(
            $this->rules(),
            [
                'form.tipo_nome.required'        => 'Informe o Tipo.',
                'form.material_nome.required'    => 'Informe o Material.',
                'form.schedule_nome.required'    => 'Informe o Schedule.',
                'form.extremidade_nome.required' => 'Informe a Extremidade.',
                'form.diametro_nome.required'    => 'Informe o Diâmetro.',

                'form.hh_un.required' => 'Informe o HH/UN.',
                'form.kg_hh.required' => 'Informe o KG/HH.',
                'form.kg_un.required' => 'Informe o KG/UN.',
                'form.m2_un.required' => 'Informe o M2/UN.',
            ]
        )['form'];

        // ✅ soma cargos = 100%
        $sum = 0.0;
        foreach ($this->percentFields() as $f) {
            $sum += (float) ($data[$f] ?? 0);
        }

        if (abs($sum - 1.0) > 0.0001) {
            $this->addError('form.lider', 'A soma dos cargos deve ser 100% (ex: 10% + 20% + ... = 100%).');
            return;
        }

        // ✅ resolve/cria FK por nomes
        $tipoId        = $this->resolveOrCreate(StdTubTipo::class, $data['tipo_nome']);
        $materialId    = $this->resolveOrCreate(StdTubMaterial::class, $data['material_nome']);
        $scheduleId    = $this->resolveOrCreate(StdTubSchedule::class, $data['schedule_nome']);
        $extremidadeId = $this->resolveOrCreate(StdTubExtremidade::class, $data['extremidade_nome']);
        $diametroId    = $this->resolveOrCreate(StdTubDiametro::class, $data['diametro_nome']);

        // ✅ payload final com ids
        $payload = $data;
        unset($payload['tipo_nome'], $payload['material_nome'], $payload['schedule_nome'], $payload['extremidade_nome'], $payload['diametro_nome']);

        $payload['std_tub_tipo_id']        = $tipoId;
        $payload['std_tub_material_id']    = $materialId;
        $payload['std_tub_schedule_id']    = $scheduleId;
        $payload['std_tub_extremidade_id'] = $extremidadeId;
        $payload['std_tub_diametro_id']    = $diametroId;

        // ✅ valida combinação única
        $exists = StdTUB::query()
            ->where('std_tub_tipo_id', $tipoId)
            ->where('std_tub_material_id', $materialId)
            ->where('std_tub_schedule_id', $scheduleId)
            ->where('std_tub_extremidade_id', $extremidadeId)
            ->where('std_tub_diametro_id', $diametroId)
            ->when($this->stdTUBId, fn($q) => $q->where('id', '!=', $this->stdTUBId))
            ->exists();

        if ($exists) {
            $this->addError('form.diametro_nome', 'Esta combinação já existe.');
            return;
        }

        $this->stdTUBId
            ? StdTUB::findOrFail($this->stdTUBId)->update($payload)
            : StdTUB::create($payload);

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('mlstandard::livewire.modal.std-tub.create-edit');
    }
}


