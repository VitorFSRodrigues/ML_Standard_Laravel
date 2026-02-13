<?php

namespace App\Services;

use App\Models\TriagemPergunta;
use Illuminate\Support\Facades\DB;

class TriagemScoring
{
    /** Soma dos pesos com resposta V ou F (equivale a Total - NA). */
    public function computePesoNA(int $triagemId): float
    {
        return (float) DB::table('triagem_pergunta as tp')
            ->join('perguntas as p', 'p.id', '=', 'tp.pergunta_id')
            ->where('tp.triagem_id', $triagemId)
            ->whereIn('tp.resposta', ['V', 'F'])
            ->sum('p.peso');
    }

    /**
     * Subtotal por linha:
     * - Peso_final = (peso_linha / Peso_NA) * 100   (para V ou F)
     * - Subtotal exibido: Peso_final somente quando resposta == 'V', caso contrário 0
     * - Evita divisão por zero.
     */
    public function computeSubtotal(TriagemPergunta $row, float $pesoNA): float
    {
        if ($pesoNA <= 0.0) {
            return 0.0;
        }

        $pesoLinha = (float) ($row->pergunta->peso ?? 0);

        $pesoFinal = in_array($row->resposta, ['V', 'F'], true)
            ? ($pesoLinha / $pesoNA) * 100.0
            : 0.0;

        $subtotal = ($row->resposta === 'V') ? $pesoFinal : 0.0;

        return round($subtotal, 1);
    }
}
