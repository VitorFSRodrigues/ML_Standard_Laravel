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
        return view('livewire.modal.dicionarios.confirm-delete');
    }
}
