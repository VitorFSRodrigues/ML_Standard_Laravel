<?php

namespace App\Livewire\Modal\Acervo;

use App\Models\Triagem;
use LivewireUI\Modal\ModalComponent;

class ConfirmReactivate extends ModalComponent
{
    public int $triagemId;

    public static function destroyOnClose(): bool { return true; }

    public function confirmar(): void
    {
        $t = Triagem::findOrFail($this->triagemId);
        $t->update(['status' => true]); // não muda destino

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.acervo.confirm-reactivate');
    }
}
