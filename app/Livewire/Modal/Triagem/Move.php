<?php

namespace App\Livewire\Modal\Triagem;

use App\Enums\DestinoTriagem;
use App\Models\{Triagem, TriagemMovimento, Orcamentista};
use Illuminate\Support\Facades\Auth;
use LivewireUI\Modal\ModalComponent;

class Move extends ModalComponent
{
    public int $triagemId;
    public string $atual = 'triagem';
    public string $destino = 'acervo';

    // select de orçamentista (somente quando destino = orcamento)
    public ?int $orcamentistaAtualId = null;   // quem detém hoje (se destino atual for orçamento)
    public ?int $orcamentistaId = null;        // alvo quando destino = orçamento
    public array $optsOrcamentistas = [];      // select

    public static function destroyOnClose(): bool { return true; }

    public function mount(
        int $triagemId,
        string $atual = 'triagem',
        ?int $orcamentistaAtualId = null
    ): void {
        $this->triagemId = $triagemId;
        $this->atual     = $atual;
        $this->orcamentistaAtualId = $orcamentistaAtualId;

        // opções de destino
        $this->destino = $atual === DestinoTriagem::TRIAGEM->value ? DestinoTriagem::ACERVO->value
                   : ($atual === DestinoTriagem::ACERVO->value   ? DestinoTriagem::TRIAGEM->value
                                                                : DestinoTriagem::ORCAMENTO->value);

        // carrega lista de orçamentistas (para quando destino = orçamento)
        $this->optsOrcamentistas = Orcamentista::orderBy('nome')
            ->get(['id','nome'])
            ->map(fn($o) => ['id' => $o->id, 'nome' => $o->nome])
            ->all();
    }

    public function confirmar(): void
    {
        $t = Triagem::findOrFail($this->triagemId);
        $deDestino = $t->destino->value ?? (string)$t->destino;

        $dados = [
            'destino'  => $this->destino,
            'moved_at' => now(),
            'moved_by' => Auth::id(),
        ];

        // quando escolher "orçamento", exigir um orçamentista alvo
        if ($this->destino === DestinoTriagem::ORCAMENTO->value) {
            if (!$this->orcamentistaId || !is_numeric($this->orcamentistaId)) {
                $this->addError('orcamentistaId', 'Selecione um orçamentista.');
                return;
            }
            // evita mover para o mesmo
            if ($this->orcamentistaAtualId && (int)$this->orcamentistaAtualId === (int)$this->orcamentistaId) {
                $this->addError('orcamentistaId', 'Selecione um orçamentista diferente.');
                return;
            }
            $dados['status'] = true; // mantém ativo
            $dados['orcamentista_id'] = (int) $this->orcamentistaId;
        } else {
            // triagem/acervo não precisam de orçamentista
            $dados['orcamentista_id'] = null;
        }

        $t->update($dados);

        // log (se tiver colunas de from/to orcamentista, preencha; senão mantenha simples)
        TriagemMovimento::create([
            'triagem_id'            => $t->id,
            'de'                    => $deDestino,
            'para'                  => $this->destino,
            'from_orcamentista_id'  => $this->orcamentistaAtualId,
            'to_orcamentista_id'    => $dados['orcamentista_id'] ?? null,
            'user_id'               => Auth::id(),
            'moved_at'              => now(),
        ]);

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modal.triagem.move', [
            'opcoes' => [
                DestinoTriagem::TRIAGEM->value   => 'Triagem',
                DestinoTriagem::ACERVO->value    => 'Acervo',
                DestinoTriagem::ORCAMENTO->value => 'Orçamento',
            ],
        ]);
    }
}
