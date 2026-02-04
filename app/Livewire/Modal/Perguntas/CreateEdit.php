<?php

namespace App\Livewire\Modal\Perguntas;

use App\Models\Pergunta;
use LivewireUI\Modal\ModalComponent;

class CreateEdit extends ModalComponent
{
    public int|string|null $perguntaId = null;

    public array $form = [
        'descricao' => '',
        'peso'      => '',
        'padrao'    => false, // checkbox
    ];

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int|string|null $perguntaId = null): void
    {
        $this->perguntaId = $perguntaId ? (int) $perguntaId : null;

        if ($this->perguntaId) {
            $p = Pergunta::findOrFail($this->perguntaId);
            $this->form = [
                'descricao' => $p->descricao,
                'peso'      => (string) $p->peso,
                'padrao'    => (bool) $p->padrao,
            ];
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.descricao' => ['required','string','max:255'],
            'form.peso'      => ['required','integer','min:0'],
            'form.padrao'    => ['boolean'],
        ])['form'];

        // garante bool mesmo se vier null
        $data['padrao'] = (bool) ($data['padrao'] ?? false);

        $this->perguntaId
            ? Pergunta::findOrFail($this->perguntaId)->update($data)
            : Pergunta::create($data);

        // atualiza grid e fecha modal
        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.perguntas.create-edit');
    }
}
