<?php

namespace App\Modules\MLStandard\Livewire\Modal\StdELE;

use App\Modules\MLStandard\Models\StdELE;
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public int|string $stdELEId;

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int|string $stdELEId): void
    {
        $this->stdELEId = (int) $stdELEId;
    }

    public function delete(): void
    {
        StdELE::findOrFail($this->stdELEId)->delete();

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('mlstandard::livewire.modal.std-ele.confirm-delete');
    }
}


