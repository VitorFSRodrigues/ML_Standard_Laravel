<?php

namespace App\Services;

use App\Models\MlFeedbackSample;
use App\Models\OrcMLstdItem;
use App\Models\Varredura;

class MlFeedbackService
{
    /**
     * Armazena linha automaticamente quando houver alguma prob < threshold (default 90).
     *
     * $mlPredNames (exemplos):
     *  ELE => ['tipo'=>..., 'material'=>..., 'conexao'=>..., 'espessura'=>..., 'extremidade'=>..., 'dimensao'=>...]
     *  TUB => ['tipo'=>..., 'material'=>..., 'schedule'=>..., 'extremidade'=>..., 'diametro'=>...]
     */
    public function storeLowConfidence(
        OrcMLstdItem $item,
        array $mlPredNames,
        ?string $probStr,
        ?int $userId = null,
        int $threshold = 90
    ): ?MlFeedbackSample {
        $disc = strtoupper((string) $item->disciplina);

        $minProb = $this->minProbFromString($probStr);

        // se não tem prob ou é >= threshold, não precisa guardar como low confidence
        if ($minProb === null || $minProb >= $threshold) {
            return null;
        }

        // upsert por item
        $sample = MlFeedbackSample::firstOrNew([
            'orc_ml_std_item_id' => $item->id,
            'disciplina'         => $disc,
        ]);

        // se já existe e foi editado, motivo vira BOTH
        $newReason = MlFeedbackSample::REASON_LOW_CONFIDENCE;
        if ($sample->exists && (bool) $sample->was_edited === true) {
            $newReason = MlFeedbackSample::REASON_BOTH;
        }

        // preenche campos
        $sample->orc_ml_std_id      = (int) $item->orc_ml_std_id;
        $sample->ordem             = (int) ($item->ordem ?? 0);
        $sample->descricao_original = (string) ($item->descricao ?? '');

        $sample->ml_pred_json       = $mlPredNames;
        $sample->ml_prob_str        = $probStr;
        $sample->ml_min_prob        = $minProb;

        $sample->reason             = $newReason;
        $sample->status             = $sample->status ?: MlFeedbackSample::STATUS_NAO_REVISADO;

        // created_by só setamos na primeira criação (não sobrescreve)
        if (!$sample->exists && $userId) {
            $sample->created_by = $userId;
        }
        if (!$sample->exists) {
            $sample->varredura_id = $this->currentVarreduraId();
        }

        $sample->save();

        return $sample;
    }

    /**
     * Armazena linha sempre que usuário editar (mesmo que prob > 90).
     *
     * $userFinalNames:
     *  ELE => ['tipo'=>..., 'material'=>..., 'conexao'=>..., 'espessura'=>..., 'extremidade'=>..., 'dimensao'=>...]
     *  TUB => ['tipo'=>..., 'material'=>..., 'schedule'=>..., 'extremidade'=>..., 'diametro'=>...]
     *
     * $editedFields: ex ['tipo','material'] etc
     */
    public function storeUserEdit(
        OrcMLstdItem $item,
        array $userFinalNames,
        array $editedFields,
        ?int $userId = null
    ): MlFeedbackSample {
        $disc = strtoupper((string) $item->disciplina);

        // upsert por item
        $sample = MlFeedbackSample::firstOrNew([
            'orc_ml_std_item_id' => $item->id,
            'disciplina'         => $disc,
        ]);

        // motivo: USER_EDIT ou BOTH (se já tinha low_confidence)
        $newReason = MlFeedbackSample::REASON_USER_EDIT;
        if ($sample->exists && $sample->reason === MlFeedbackSample::REASON_LOW_CONFIDENCE) {
            $newReason = MlFeedbackSample::REASON_BOTH;
        }
        if ($sample->exists && $sample->reason === MlFeedbackSample::REASON_BOTH) {
            $newReason = MlFeedbackSample::REASON_BOTH;
        }

        // Campos base
        $sample->orc_ml_std_id       = (int) $item->orc_ml_std_id;
        $sample->ordem              = (int) ($item->ordem ?? 0);
        $sample->descricao_original  = (string) ($item->descricao ?? '');

        // Campos user edit
        $sample->was_edited          = true;
        $sample->user_final_json     = $userFinalNames;

        // merge: se já tinha edited_fields, junta sem duplicar
        $existing = is_array($sample->edited_fields_json) ? $sample->edited_fields_json : [];
        $merged   = array_values(array_unique(array_merge($existing, $editedFields)));
        $sample->edited_fields_json  = $merged;

        // tenta guardar prob atual do item se existir (não obrigatório)
        if (!$sample->ml_prob_str && !empty($item->prob)) {
            $sample->ml_prob_str = $item->prob;
        }
        if (!$sample->ml_min_prob && !empty($item->prob)) {
            $sample->ml_min_prob = $this->minProbFromString($item->prob);
        }

        $sample->reason              = $newReason;
        $sample->status              = $sample->status ?: MlFeedbackSample::STATUS_NAO_REVISADO;

        // created_by só setamos na primeira criação (não sobrescreve)
        if (!$sample->exists && $userId) {
            $sample->created_by = $userId;
        }
        if (!$sample->exists) {
            $sample->varredura_id = $this->currentVarreduraId();
        }

        $sample->save();

        return $sample;
    }

    /**
     * Retorna o menor percentual inteiro da string "99/88/77..."
     * Se string inválida ou vazia => null
     */
    public function minProbFromString(?string $probStr): ?int
    {
        if (!$probStr) return null;

        $parts = array_map('trim', explode('/', $probStr));
        $nums  = [];

        foreach ($parts as $p) {
            if ($p === '') continue;

            // aceita "90" ou "90.0"
            if (is_numeric($p)) {
                $nums[] = (int) round((float) $p);
            }
        }

        if (count($nums) === 0) {
            return null;
        }

        return min($nums);
    }

    private function currentVarreduraId(): ?int
    {
        return Varredura::query()->orderByDesc('id')->value('id');
    }
}
