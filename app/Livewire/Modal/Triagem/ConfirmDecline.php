<?php

namespace App\Livewire\Modal\Triagem;

use App\Enums\DestinoTriagem;
use App\Models\Triagem;
use App\Models\TriagemMovimento;
use Illuminate\Support\Facades\Auth;
use LivewireUI\Modal\ModalComponent;

class ConfirmDecline extends ModalComponent
{
    public int $triagemId;

    public static function destroyOnClose(): bool { return true; }

    public function confirmar(): void
    {
        $t = Triagem::findOrFail($this->triagemId);

        // 1) status = false
        // 2) mover para ACERVO
        $t->update([
            'status'  => false,
            'destino' => DestinoTriagem::ACERVO->value,
            'moved_at'=> now(),
            'moved_by'=> Auth::id(),
        ]);

        // logar movimento (triagem -> acervo)
        TriagemMovimento::create([
            'triagem_id' => $t->id,
            'de'         => 'triagem',
            'para'       => 'acervo',
            'user_id'    => Auth::id(),
            'moved_at'   => now(),
        ]);

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.triagem.confirm-decline');
    }
}
