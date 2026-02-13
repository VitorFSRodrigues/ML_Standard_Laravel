<?php

namespace App\Modules\MLRetreinamentos\Livewire\Modal\Dicionarios;

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
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public string $dictKey;
    public int $dictItemId;

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(string $dictKey, int $dictItemId): void
    {
        $this->dictKey = $dictKey;
        $this->dictItemId = $dictItemId;
    }

    public function delete(): void
    {
        $model = $this->modelFor($this->dictKey);
        $model::findOrFail($this->dictItemId)->delete();

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    private function modelFor(string $dictKey): string
    {
        $map = $this->dictMap();

        if (!isset($map[$dictKey])) {
            abort(404);
        }

        return $map[$dictKey];
    }

    private function dictMap(): array
    {
        return [
            'dict_ele_tipo' => DictEleTipo::class,
            'dict_ele_material' => DictEleMaterial::class,
            'dict_ele_conexao' => DictEleConexao::class,
            'dict_ele_espessura' => DictEleEspessura::class,
            'dict_ele_extremidade' => DictEleExtremidade::class,
            'dict_ele_dimensao' => DictEleDimensao::class,
            'dict_tub_tipo' => DictTubTipo::class,
            'dict_tub_material' => DictTubMaterial::class,
            'dict_tub_schedule' => DictTubSchedule::class,
            'dict_tub_extremidade' => DictTubExtremidade::class,
            'dict_tub_diametro' => DictTubDiametro::class,
        ];
    }

    public function render()
    {
        return view('mlretreinamentos::livewire.modal.dicionarios.confirm-delete');
    }
}
