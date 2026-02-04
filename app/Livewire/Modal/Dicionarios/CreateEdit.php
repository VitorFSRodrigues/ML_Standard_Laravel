<?php

namespace App\Livewire\Modal\Dicionarios;

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
use App\Models\Varredura;
use LivewireUI\Modal\ModalComponent;

class CreateEdit extends ModalComponent
{
    public ?string $dictKey = null;
    public ?int $dictItemId = null;

    public array $form = [
        'dict_key' => '',
        'Termo' => '',
        'Descricao_Padrao' => '',
    ];

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(?string $dictKey = null, ?int $dictItemId = null): void
    {
        $this->dictKey = $dictKey;
        $this->dictItemId = $dictItemId;

        if ($dictKey && $dictItemId) {
            $model = $this->modelFor($dictKey);
            $row = $model::findOrFail($dictItemId);

            $this->form = [
                'dict_key' => $dictKey,
                'Termo' => (string) ($row->Termo ?? ''),
                'Descricao_Padrao' => (string) ($row->Descricao_Padrao ?? ''),
            ];
        }
    }

    public function save(): void
    {
        $allowed = array_keys($this->dictMap());

        $rules = [
            'form.dict_key' => ['required', 'string', 'in:' . implode(',', $allowed)],
            'form.Termo' => ['required', 'string'],
            'form.Descricao_Padrao' => ['required', 'string'],
        ];

        $data = $this->validate($rules)['form'];

        $model = $this->modelFor($data['dict_key']);

        if ($this->dictItemId) {
            $model::findOrFail($this->dictItemId)->update([
                'Termo' => $data['Termo'],
                'Descricao_Padrao' => $data['Descricao_Padrao'],
            ]);
        } else {
            $model::create([
                'Termo' => $data['Termo'],
                'Descricao_Padrao' => $data['Descricao_Padrao'],
                'Revisao' => $this->currentRevisionFor($data['dict_key']),
            ]);
        }

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function dictOptions(): array
    {
        $options = [];
        foreach ($this->dictMap() as $key => $meta) {
            $options[$key] = $meta['label'];
        }

        return $options;
    }

    private function modelFor(string $dictKey): string
    {
        $map = $this->dictMap();

        if (!isset($map[$dictKey])) {
            abort(404);
        }

        return $map[$dictKey]['model'];
    }

    private function currentRevisionFor(string $dictKey): int
    {
        $map = $this->dictMap();
        $disc = $map[$dictKey]['disciplina'] ?? 'ELE';

        $current = Varredura::query()->orderByDesc('id')->first();
        if (!$current) {
            return 0;
        }

        return $disc === 'TUB'
            ? (int) $current->revisao_tub
            : (int) $current->revisao_ele;
    }

    private function dictMap(): array
    {
        return [
            'dict_ele_tipo' => [
                'label' => 'ELE - Tipo',
                'model' => DictEleTipo::class,
                'disciplina' => 'ELE',
            ],
            'dict_ele_material' => [
                'label' => 'ELE - Material',
                'model' => DictEleMaterial::class,
                'disciplina' => 'ELE',
            ],
            'dict_ele_conexao' => [
                'label' => 'ELE - Conexao',
                'model' => DictEleConexao::class,
                'disciplina' => 'ELE',
            ],
            'dict_ele_espessura' => [
                'label' => 'ELE - Espessura',
                'model' => DictEleEspessura::class,
                'disciplina' => 'ELE',
            ],
            'dict_ele_extremidade' => [
                'label' => 'ELE - Extremidade',
                'model' => DictEleExtremidade::class,
                'disciplina' => 'ELE',
            ],
            'dict_ele_dimensao' => [
                'label' => 'ELE - Dimensao',
                'model' => DictEleDimensao::class,
                'disciplina' => 'ELE',
            ],
            'dict_tub_tipo' => [
                'label' => 'TUB - Tipo',
                'model' => DictTubTipo::class,
                'disciplina' => 'TUB',
            ],
            'dict_tub_material' => [
                'label' => 'TUB - Material',
                'model' => DictTubMaterial::class,
                'disciplina' => 'TUB',
            ],
            'dict_tub_schedule' => [
                'label' => 'TUB - Schedule',
                'model' => DictTubSchedule::class,
                'disciplina' => 'TUB',
            ],
            'dict_tub_extremidade' => [
                'label' => 'TUB - Extremidade',
                'model' => DictTubExtremidade::class,
                'disciplina' => 'TUB',
            ],
            'dict_tub_diametro' => [
                'label' => 'TUB - Diametro',
                'model' => DictTubDiametro::class,
                'disciplina' => 'TUB',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.modal.dicionarios.create-edit');
    }
}
