<?php

namespace App\Livewire\Modal\Orcamentistas;

use App\Models\Orcamentista;
use Illuminate\Validation\Rule;
use LivewireUI\Modal\ModalComponent;

class CreateEdit extends ModalComponent
{
    public ?int $orcamentistaId = null;

    public array $form = [
        'nome'  => '',
        'email' => '',
    ];

    public static function destroyOnClose(): bool { return true; }

    public function mount(?int $orcamentistaId = null): void
    {
        $this->orcamentistaId = $orcamentistaId;

        if ($orcamentistaId) {
            $o = Orcamentista::findOrFail($orcamentistaId);
            $this->form = [
                'nome'  => $o->nome,
                'email' => $o->email,
            ];
        }
    }

    public function save(): void
    {
        $rules = [
            'form.nome'  => ['required','string','max:255'],
            'form.email' => [
                'required','email','max:255',
                Rule::unique('orcamentistas','email')->ignore($this->orcamentistaId),
            ],
        ];

        $data = $this->validate($rules)['form'];

        if ($this->orcamentistaId) {
            Orcamentista::findOrFail($this->orcamentistaId)->update($data);
        } else {
            Orcamentista::create($data);
        }

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.orcamentistas.create-edit');
    }
}
