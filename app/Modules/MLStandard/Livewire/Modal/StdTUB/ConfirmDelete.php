<?php

namespace App\Modules\MLStandard\Livewire\Modal\StdTUB;

use App\Modules\MLStandard\Models\StdTUB;
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public int|string $stdTUBId;

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int|string $stdTUBId): void
    {
        $this->stdTUBId = (int) $stdTUBId;
    }

    public function delete(): void
    {
        StdTUB::findOrFail($this->stdTUBId)->delete();

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('mlstandard::livewire.modal.std-tub.confirm-delete');
    }
}


