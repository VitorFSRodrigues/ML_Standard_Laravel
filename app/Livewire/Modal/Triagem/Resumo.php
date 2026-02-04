<?php

namespace App\Livewire\Modal\Triagem;

use App\Models\TriagemPergunta;
use App\Services\TriagemScoring;
use Livewire\Component;

class Resumo extends Component
{
    public int $triagemId;
    public float $total = 0.0;
    public string $nivel = 'Baixa';
    public int $probabilidade = 10;   // ← NOVO
    
    protected $listeners = ['triagem-resumo:refresh' => 'refreshResumo'];

    public function mount(int $triagemId): void
    {
        $this->triagemId = $triagemId;
        $this->recalcular();
    }

    public function refreshResumo(int $triagemId = null): void
    {
        if ($triagemId === null || $triagemId === $this->triagemId) {
            $this->recalcular();
        }
    }

    private function recalcular(): void
    {
        /** @var TriagemScoring $scoring */
        $scoring = app(TriagemScoring::class);

        $pesoNA = $scoring->computePesoNA($this->triagemId);

        $linhas = TriagemPergunta::with(['pergunta:id,peso'])
            ->where('triagem_id', $this->triagemId)
            ->whereHas('pergunta', fn ($q) => $q->where('padrao', true))
            ->get();

        /** @var float $soma */
        $soma = $linhas->sum(
            fn (TriagemPergunta $row) => $scoring->computeSubtotal($row, $pesoNA)
        );

        $this->total = round($soma, 2);

        // Nível: >60 Alta, >40 Média, senão Baixa
        $this->nivel = $this->total > 60 ? 'Alta' : ($this->total > 40 ? 'Média' : 'Baixa');

        // Probabilidade (1..10)
        $this->probabilidade = match (true) {
            $this->total >= 90 => 1,
            $this->total >= 80 => 2,
            $this->total >= 70 => 3,
            $this->total >= 60 => 4,
            $this->total >= 50 => 5,
            $this->total >= 40 => 6,
            $this->total >= 30 => 7,
            $this->total >= 20 => 8,
            $this->total >= 10 => 9,
            default            => 10,
        };
        $this->dispatch('reloadPowergrid');
    }

    public function render()
    {
        return view('livewire.modal.triagem.resumo');
    }
}
