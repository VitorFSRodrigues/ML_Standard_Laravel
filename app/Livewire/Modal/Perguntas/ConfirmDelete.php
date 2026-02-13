<?php

namespace App\Livewire\Modal\Perguntas;

use App\Models\Pergunta;
use LivewireUI\Modal\ModalComponent;

class ConfirmDelete extends ModalComponent
{
    public int|string $perguntaId;

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int|string $perguntaId): void
    {
        $this->perguntaId = (int) $perguntaId;
    }

    public function delete(): void
    {
        Pergunta::findOrFail($this->perguntaId)->delete();

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.perguntas.confirm-delete');
    }
}
