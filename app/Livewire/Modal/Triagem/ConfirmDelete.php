<?php

namespace App\Livewire\Modal\Triagem;

use App\Models\Triagem;
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public int|string $triagemId;

    public static function destroyOnClose(): bool { return true; }

    public function mount(int|string $triagemId): void
    {
        $this->triagemId = (int) $triagemId;
    }

    public function delete(): void
    {
        Triagem::findOrFail($this->triagemId)->delete();
        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.triagem.confirm-delete');
    }
}
