<?php

namespace App\Observers;

use App\Models\Pergunta;
use App\Models\Triagem;
use App\Models\TriagemPergunta;
use App\Models\Requisito;

class TriagemObserver
{
    /**
     * Dispara após criar uma triagem.
     */
    public function created(Triagem $triagem): void
    {
        // pega só perguntas padrão
        $perguntasIds = Pergunta::where('padrao', true)->pluck('id')->all();

        if (empty($perguntasIds)) {
            return;
        }

        // evita duplicar se algum fluxo salvar duas vezes
        $jaVinculadas = TriagemPergunta::where('triagem_id', $triagem->id)
            ->pluck('pergunta_id')
            ->all();

        $faltantes = array_diff($perguntasIds, $jaVinculadas);

        if (empty($faltantes)) {
            return;
        }

        $now = now();
        $rows = array_map(fn ($pid) => [
            'triagem_id'  => $triagem->id,
            'pergunta_id' => $pid,
            'resposta'    => 'NA',      // default
            'observacao'  => null,
            'created_at'  => $now,
            'updated_at'  => $now,
        ], $faltantes);

        TriagemPergunta::insert($rows);

        // Garante que exista o registro de requisitos (1:1)
        Requisito::firstOrCreate(
            ['triagem_id' => $triagem->id],
            [
                'icms_percent' => config('fiscal.icms_default'), 
            ]
        );
    }
}
