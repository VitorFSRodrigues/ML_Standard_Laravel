<?php

namespace App\Livewire\Modal\Triagem;

use App\Models\TriagemPergunta;
use LivewireUI\Modal\ModalComponent;

class EditarResposta extends ModalComponent
{
    public int $triagemPerguntaId;
    public string $resposta = 'NA';

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int $triagemPerguntaId): void
    {
        $this->triagemPerguntaId = $triagemPerguntaId;

        $row = TriagemPergunta::findOrFail($triagemPerguntaId);
        $this->resposta = in_array($row->resposta, ['V','F','NA'], true) ? $row->resposta : 'NA';
    }

    public function save(): void
    {
        $val = in_array($this->resposta, ['V','F','NA'], true) ? $this->resposta : 'NA';

        TriagemPergunta::findOrFail($this->triagemPerguntaId)
            ->update(['resposta' => $val]);

        // Atualiza grid
        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.triagem.editar-resposta');
    }
}
