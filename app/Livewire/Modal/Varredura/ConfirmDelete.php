<?php

namespace App\Livewire\Modal\Varredura;

use App\Models\Varredura;
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public int $varreduraId;

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int $varreduraId): void
    {
        $this->varreduraId = $varreduraId;
    }

    public function delete(): void
    {
        Varredura::findOrFail($this->varreduraId)->delete();

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.varredura.confirm-delete');
    }
}
