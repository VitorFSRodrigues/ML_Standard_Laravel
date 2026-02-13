<?php
// app/Livewire/Modal/Fases/ConfirmDelete.php
namespace App\Livewire\Modal\Fases;

use App\Models\Fase;
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public int $faseId;

    public static function destroyOnClose(): bool { return true; }

    public function mount(int $faseId): void
    {
        $this->faseId = $faseId;
    }

    public function confirmar(): void
    {
        Fase::findOrFail($this->faseId)->delete();
        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.fases.confirm-delete');
    }
}
