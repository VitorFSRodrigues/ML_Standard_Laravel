<?php

namespace App\Modules\MLRetreinamentos\Livewire\Modal\Varredura;

use App\Modules\MLRetreinamentos\Models\DictEleConexao;
use App\Modules\MLRetreinamentos\Models\DictEleDimensao;
use App\Modules\MLRetreinamentos\Models\DictEleEspessura;
use App\Modules\MLRetreinamentos\Models\DictEleExtremidade;
use App\Modules\MLRetreinamentos\Models\DictEleMaterial;
use App\Modules\MLRetreinamentos\Models\DictEleTipo;
use App\Modules\MLRetreinamentos\Models\DictTubDiametro;
use App\Modules\MLRetreinamentos\Models\DictTubExtremidade;
use App\Modules\MLRetreinamentos\Models\DictTubMaterial;
use App\Modules\MLRetreinamentos\Models\DictTubSchedule;
use App\Modules\MLRetreinamentos\Models\DictTubTipo;
use App\Modules\MLRetreinamentos\Models\Varredura;
use LivewireUI\Modal\ModalComponent;

class AddDictItem extends ModalComponent
{
    public string $disciplina = 'ELE';
    public ?int $varreduraId = null;
    public string $dict = '';
    public string $termo = '';
    public string $descricaoPadrao = '';

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(string $disciplina = 'ELE', ?int $varreduraId = null): void
    {
        $this->disciplina = strtoupper($disciplina);
        $this->varreduraId = $varreduraId;
    }

    public function save(): void
    {
        $allowed = array_keys($this->dictMap());

        $rules = [
            'dict' => ['required', 'string', 'in:' . implode(',', $allowed)],
            'termo' => ['required', 'string'],
            'descricaoPadrao' => ['required', 'string'],
        ];

        $this->validate($rules, [], [
            'dict' => 'dicionário',
            'termo' => 'termo',
            'descricaoPadrao' => 'descrição padrão',
        ]);

        $map = $this->dictMap();
        $modelClass = $map[$this->dict] ?? null;
        if (!$modelClass) {
            return;
        }

        $rev = $this->currentRevision();

        $modelClass::create([
            'Termo' => $this->termo,
            'Descricao_Padrao' => $this->descricaoPadrao,
            'Revisao' => $rev,
        ]);

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function dictOptions(): array
    {
        $labels = [
            'tipo' => 'Tipo',
            'material' => 'Material',
            'conexao' => 'Conexão',
            'espessura' => 'Espessura',
            'extremidade' => 'Extremidade',
            'dimensao' => 'Dimensão',
            'schedule' => 'Schedule',
            'diametro' => 'Diâmetro',
        ];

        $options = [];
        foreach ($this->dictMap() as $key => $class) {
            $options[$key] = $labels[$key] ?? $key;
        }

        return $options;
    }

    private function dictMap(): array
    {
        if ($this->disciplina === 'TUB') {
            return [
                'tipo' => DictTubTipo::class,
                'material' => DictTubMaterial::class,
                'schedule' => DictTubSchedule::class,
                'extremidade' => DictTubExtremidade::class,
                'diametro' => DictTubDiametro::class,
            ];
        }

        return [
            'tipo' => DictEleTipo::class,
            'material' => DictEleMaterial::class,
            'conexao' => DictEleConexao::class,
            'espessura' => DictEleEspessura::class,
            'extremidade' => DictEleExtremidade::class,
            'dimensao' => DictEleDimensao::class,
        ];
    }

    private function currentRevision(): int
    {
        $current = $this->varreduraId
            ? Varredura::query()->find($this->varreduraId)
            : Varredura::query()->orderByDesc('id')->first();
        if (!$current) {
            return 0;
        }

        return $this->disciplina === 'TUB'
            ? (int) $current->revisao_tub
            : (int) $current->revisao_ele;
    }

    public function render()
    {
        return view('mlretreinamentos::livewire.modal.varredura.add-dict-item');
    }
}
