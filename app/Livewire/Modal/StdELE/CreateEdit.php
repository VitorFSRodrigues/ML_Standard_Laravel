<?php

namespace App\Livewire\Modal\StdELE;

use App\Models\StdELE;
use App\Models\StdEleTipo;
use App\Models\StdEleMaterial;
use App\Models\StdEleConexao;
use App\Models\StdEleEspessura;
use App\Models\StdEleExtremidade;
use App\Models\StdEleDimensao;
use LivewireUI\Modal\ModalComponent;

class CreateEdit extends ModalComponent
{
    public int|string|null $stdELEId = null;

    // ✅ listas para sugestões (datalist)
    public array $tipos = [];
    public array $materiais = [];
    public array $conexoes = [];
    public array $espessuras = [];
    public array $extremidades = [];
    public array $dimensoes = [];

    public array $form = [
        // ✅ agora são nomes (texto corrido)
        'tipo_nome'        => '',
        'material_nome'    => '',
        'conexao_nome'     => '',
        'espessura_nome'   => '',
        'extremidade_nome' => '',
        'dimensao_nome'    => '',

        'std' => 0,

        // ✅ percentuais
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

    public function mount(int|string|null $stdELEId = null): void
    {
        $this->stdELEId = $stdELEId ? (int)$stdELEId : null;

        // ✅ datalist (só nomes)
        $this->tipos        = StdEleTipo::query()->orderBy('nome')->pluck('nome')->toArray();
        $this->materiais    = StdEleMaterial::query()->orderBy('nome')->pluck('nome')->toArray();
        $this->conexoes     = StdEleConexao::query()->orderBy('nome')->pluck('nome')->toArray();
        $this->espessuras   = StdEleEspessura::query()->orderBy('nome')->pluck('nome')->toArray();
        $this->extremidades = StdEleExtremidade::query()->orderBy('nome')->pluck('nome')->toArray();
        $this->dimensoes    = StdEleDimensao::query()->orderBy('nome')->pluck('nome')->toArray();

        if ($this->stdELEId) {
            $row = StdELE::with(['tipo','material','conexao','espessura','extremidade','dimensao'])
                ->findOrFail($this->stdELEId);

            $this->form = array_merge($this->form, [
                'tipo_nome'        => $row->tipo?->nome ?? '',
                'material_nome'    => $row->material?->nome ?? '',
                'conexao_nome'     => $row->conexao?->nome ?? '',
                'espessura_nome'   => $row->espessura?->nome ?? '',
                'extremidade_nome' => $row->extremidade?->nome ?? '',
                'dimensao_nome'    => $row->dimensao?->nome ?? '',

                'std' => $row->std,

                // percentuais
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
        $txt = trim((string)$txt);
        $txt = mb_strtoupper($txt, 'UTF-8');
        // remove múltiplos espaços
        $txt = preg_replace('/\s+/', ' ', $txt);
        return $txt ?? '';
    }

    private function rules(): array
    {
        $numRule = ['nullable']; // normalizamos manualmente

        return [
            'form.tipo_nome'        => ['required', 'string', 'max:255'],
            'form.material_nome'    => ['required', 'string', 'max:255'],
            'form.conexao_nome'     => ['required', 'string', 'max:255'],
            'form.espessura_nome'   => ['required', 'string', 'max:255'],
            'form.extremidade_nome' => ['required', 'string', 'max:255'],
            'form.dimensao_nome'    => ['required', 'string', 'max:255'],

            'form.std' => ['required', 'numeric', 'min:0'],

            // percentuais (aceita string)
            'form.encarregado_mecanica' => $numRule,
            'form.encarregado_tubulacao' => $numRule,
            'form.encarregado_eletrica' => $numRule,
            'form.encarregado_andaime' => $numRule,
            'form.encarregado_civil' => $numRule,
            'form.lider' => $numRule,

            'form.mecanico_ajustador' => $numRule,
            'form.mecanico_montador' => $numRule,
            'form.encanador' => $numRule,
            'form.caldeireiro' => $numRule,
            'form.lixador' => $numRule,
            'form.montador' => $numRule,

            'form.soldador_er' => $numRule,
            'form.soldador_tig' => $numRule,
            'form.soldador_mig' => $numRule,

            'form.ponteador' => $numRule,

            'form.eletricista_controlista' => $numRule,
            'form.eletricista_montador' => $numRule,
            'form.instrumentista' => $numRule,

            'form.montador_de_andaime' => $numRule,
            'form.pintor' => $numRule,
            'form.jatista' => $numRule,
            'form.pedreiro' => $numRule,
            'form.carpinteiro' => $numRule,
            'form.armador' => $numRule,
            'form.ajudante' => $numRule,
        ];
    }

    private function resolveOrCreate(string $modelClass, string $nome): int
    {
        /** @var \Illuminate\Database\Eloquent\Model $modelClass */
        $row = $modelClass::query()->firstOrCreate(['nome' => $nome]);
        return (int) $row->id;
    }

    public function save(): void
    {
        // ✅ normaliza textos antes de validar
        $this->form['tipo_nome']        = $this->normalizeUpper($this->form['tipo_nome'] ?? '');
        $this->form['material_nome']    = $this->normalizeUpper($this->form['material_nome'] ?? '');
        $this->form['conexao_nome']     = $this->normalizeUpper($this->form['conexao_nome'] ?? '');
        $this->form['espessura_nome']   = $this->normalizeUpper($this->form['espessura_nome'] ?? '');
        $this->form['extremidade_nome'] = $this->normalizeUpper($this->form['extremidade_nome'] ?? '');
        $this->form['dimensao_nome']    = $this->normalizeUpper($this->form['dimensao_nome'] ?? '');

        // ✅ normaliza percentuais: aceita 10,05% ou 10,05
        foreach ($this->percentFields() as $f) {
            $this->form[$f] = $this->normalizePercent($this->form[$f] ?? 0);
        }

        $data = $this->validate($this->rules(), [
            'form.tipo_nome.required'        => 'Informe o Tipo.',
            'form.material_nome.required'    => 'Informe o Material.',
            'form.conexao_nome.required'     => 'Informe a Conexão.',
            'form.espessura_nome.required'   => 'Informe a Espessura.',
            'form.extremidade_nome.required' => 'Informe a Extremidade.',
            'form.dimensao_nome.required'    => 'Informe a Dimensão.',
            'form.std.required'              => 'Informe o valor do STD.',
        ])['form'];

        // ✅ valida soma 100%
        $sum = 0.0;
        foreach ($this->percentFields() as $f) {
            $sum += (float)($data[$f] ?? 0);
        }

        if (abs($sum - 1.0) > 0.0001) {
            $this->addError('form.lider', 'A soma dos cargos deve ser 100% (ex: 10% + 20% + ... = 100%).');
            return;
        }

        // ✅ resolve/cria FKs (texto -> id)
        $tipoId        = $this->resolveOrCreate(StdEleTipo::class, $data['tipo_nome']);
        $materialId    = $this->resolveOrCreate(StdEleMaterial::class, $data['material_nome']);
        $conexaoId     = $this->resolveOrCreate(StdEleConexao::class, $data['conexao_nome']);
        $espessuraId   = $this->resolveOrCreate(StdEleEspessura::class, $data['espessura_nome']);
        $extremidadeId = $this->resolveOrCreate(StdEleExtremidade::class, $data['extremidade_nome']);
        $dimensaoId    = $this->resolveOrCreate(StdEleDimensao::class, $data['dimensao_nome']);

        // ✅ monta payload final (com ids)
        $payload = $data;
        unset(
            $payload['tipo_nome'], $payload['material_nome'], $payload['conexao_nome'],
            $payload['espessura_nome'], $payload['extremidade_nome'], $payload['dimensao_nome'],
        );

        $payload['std_ele_tipo_id']        = $tipoId;
        $payload['std_ele_material_id']    = $materialId;
        $payload['std_ele_conexao_id']     = $conexaoId;
        $payload['std_ele_espessura_id']   = $espessuraId;
        $payload['std_ele_extremidade_id'] = $extremidadeId;
        $payload['std_ele_dimensao_id']    = $dimensaoId;

        // ✅ valida combinação única
        $exists = StdELE::query()
            ->where('std_ele_tipo_id', $tipoId)
            ->where('std_ele_material_id', $materialId)
            ->where('std_ele_conexao_id', $conexaoId)
            ->where('std_ele_espessura_id', $espessuraId)
            ->where('std_ele_extremidade_id', $extremidadeId)
            ->where('std_ele_dimensao_id', $dimensaoId)
            ->when($this->stdELEId, fn($q) => $q->where('id', '!=', $this->stdELEId))
            ->exists();

        if ($exists) {
            $this->addError('form.dimensao_nome', 'Esta combinação já existe.');
            return;
        }

        $this->stdELEId
            ? StdELE::findOrFail($this->stdELEId)->update($payload)
            : StdELE::create($payload);

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.std-ele.create-edit');
    }
}
