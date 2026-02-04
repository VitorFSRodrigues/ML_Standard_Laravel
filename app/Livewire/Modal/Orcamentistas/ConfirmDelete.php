<?php

namespace App\Livewire\Modal\Orcamentistas;

use App\Models\Orcamentista;
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public int $orcamentistaId;

    public static function destroyOnClose(): bool { return true; }

    public function mount(int $orcamentistaId): void
    {
        $this->orcamentistaId = $orcamentistaId;
    }

    public function delete(): void
    {
        Orcamentista::findOrFail($this->orcamentistaId)->delete();

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.orcamentistas.confirm-delete', [
            'registro' => Orcamentista::find($this->orcamentistaId),
        ]);
    }
}
