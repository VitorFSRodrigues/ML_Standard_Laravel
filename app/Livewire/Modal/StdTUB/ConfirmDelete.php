<?php

namespace App\Livewire\Modal\StdTUB;

use App\Models\StdTUB;
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
        return view('livewire.modal.std-tub.confirm-delete');
    }
}
