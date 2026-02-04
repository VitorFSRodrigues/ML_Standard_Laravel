<?php
// app/Livewire/Modal/Fases/CreateEdit.php
namespace App\Livewire\Modal\Fases;

use App\Models\Fase;
use Illuminate\Validation\Rule;
use LivewireUI\Modal\ModalComponent;

class CreateEdit extends ModalComponent
{
    public ?int $faseId = null;
    public int $triagemId;

    public array $form = [
        'revisao'    => 0,
        'versao'     => 1,
        'comentario' => '',
    ];

    public static function destroyOnClose(): bool { return true; }

    public function mount(?int $faseId = null, int $triagemId = 0): void
    {
        $this->faseId    = $faseId;
        $this->triagemId = $triagemId;

        if ($faseId) {
            $f = Fase::findOrFail($faseId);
            $this->form = [
                'revisao'    => (int)$f->revisao,
                'versao'     => (int)$f->versao,
                'comentario' => (string) ($f->comentario ?? ''),
            ];
        }
    }

    public function save(): void
    {
        $rules = [
            'form.revisao' => 'required|integer|min:0',
            'form.versao'  => [
                'required', 'integer', 'min:1',
                Rule::unique('fases', 'versao')
                    ->where(fn ($q) => $q->where('triagem_id', $this->triagemId)
                                        ->where('revisao', $this->form['revisao']))
                    ->ignore($this->faseId),
            ],
            'form.comentario' => 'nullable|string|max:255',
        ];

        $messages = [
            'form.versao.unique' => 'Já existe um fase com esta Revisão/Versão para este orçamento.',
        ];

        // (opcional) nomes mais amigáveis nos erros
        $attributes = [
            'form.revisao' => 'revisão',
            'form.versao'  => 'versão',
        ];

        $data = $this->validate($rules, $messages, $attributes)['form'];

        if ($this->faseId) {
            Fase::findOrFail($this->faseId)->update([
                'revisao'    => $data['revisao'],
                'versao'     => $data['versao'],
                'comentario' => $data['comentario'] ?? null,
            ]);
        } else {
            Fase::create([
                'triagem_id' => $this->triagemId,
                'revisao'    => $data['revisao'],
                'versao'     => $data['versao'],
                'comentario' => $data['comentario'] ?? null,
            ]);
        }

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.fases.create-edit');
    }
}
