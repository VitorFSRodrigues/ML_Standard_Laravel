<?php

namespace App\Modules\MLStandard\Livewire\Modal\OrcMLstd;

use App\Modules\MLStandard\Models\OrcMLstd;
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public int|string $orcMLstdId;

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int|string $orcMLstdId): void
    {
        $this->orcMLstdId = (int) $orcMLstdId;
    }

    public function delete(): void
    {
        OrcMLstd::findOrFail($this->orcMLstdId)->delete();

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('mlstandard::livewire.modal.orc-mlstd.confirm-delete');
    }
}



