<?php

namespace App\Livewire\Modal\Varredura;

use App\Models\ModeloMl;
use App\Models\Varredura;
use LivewireUI\Modal\ModalComponent;

class CreateEdit extends ModalComponent
{
    public ?int $varreduraId = null;

    public array $form = [
        'revisao_ele' => 0,
        'revisao_tub' => 0,
        'status_ele'  => false,
        'status_tub'  => false,
    ];

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(?int $varreduraId = null): void
    {
        $this->varreduraId = $varreduraId;

        if ($varreduraId) {
            $row = Varredura::findOrFail($varreduraId);
            $this->form = [
                'revisao_ele' => (int) $row->revisao_ele,
                'revisao_tub' => (int) $row->revisao_tub,
                'status_ele'  => (bool) $row->status_ele,
                'status_tub'  => (bool) $row->status_tub,
            ];
            return;
        }

        $this->form = [
            'revisao_ele' => $this->nextRevision('ELE'),
            'revisao_tub' => $this->nextRevision('TUB'),
            'status_ele'  => false,
            'status_tub'  => false,
        ];
    }

    private function nextRevision(string $disciplina): int
    {
        $max = ModeloMl::query()
            ->where('disciplina', $disciplina)
            ->max('revisao');

        return ((int) $max) + 1;
    }

    public function save(): void
    {
        $rules = [
            'form.revisao_ele' => ['required', 'integer', 'min:0'],
            'form.revisao_tub' => ['required', 'integer', 'min:0'],
        ];

        if ($this->varreduraId) {
            $rules['form.status_ele'] = ['boolean'];
            $rules['form.status_tub'] = ['boolean'];
        }

        $data = $this->validate($rules)['form'];

        if ($this->varreduraId) {
            Varredura::findOrFail($this->varreduraId)->update($data);
        } else {
            Varredura::create($data);
        }

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.varredura.create-edit');
    }
}
