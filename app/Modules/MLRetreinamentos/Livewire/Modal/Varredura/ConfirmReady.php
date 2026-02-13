<?php

namespace App\Modules\MLRetreinamentos\Livewire\Modal\Varredura;

use App\Modules\MLRetreinamentos\Models\MlFeedbackSample;
use App\Modules\MLRetreinamentos\Models\Varredura;
use LivewireUI\Modal\ModalComponent;

class ConfirmReady extends ModalComponent
{
    public string $disciplina = 'ELE';
    public ?int $varreduraId = null;

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(string $disciplina = 'ELE', ?int $varreduraId = null): void
    {
        $this->disciplina = strtoupper($disciplina);
        $this->varreduraId = $varreduraId;
    }

    public function confirm(): void
    {
        $current = $this->varreduraId
            ? Varredura::query()->find($this->varreduraId)
            : Varredura::query()->orderByDesc('id')->first();
        if (!$current) {
            return;
        }

        $statusField = $this->disciplina === 'TUB' ? 'status_tub' : 'status_ele';

        if ((bool) $current->{$statusField} === true) {
            return;
        }

        $current->update([$statusField => true]);

        $query = MlFeedbackSample::query()
            ->where('disciplina', $this->disciplina)
            ->where('status', MlFeedbackSample::STATUS_APROVADO);

        if ($current->id) {
            $query->where('varredura_id', $current->id);
        }

        $query->update([
            'status' => MlFeedbackSample::STATUS_TREINADO,
            'updated_at' => now(),
        ]);

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('mlretreinamentos::livewire.modal.varredura.confirm-ready');
    }
}
