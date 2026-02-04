<?php

namespace App\Services;

use App\Models\MlFeedbackSample;
use App\Models\MlTrainingQueueItem;
use Illuminate\Support\Carbon;

class MlTrainingQueueService
{
    public function approveSample(MlFeedbackSample $sample, ?int $userId = null): void
    {
        // seta aprovado no sample
        $sample->status = MlFeedbackSample::STATUS_APROVADO;
        $sample->save();

        // upsert queue item
        $queue = MlTrainingQueueItem::firstOrNew([
            'ml_feedback_sample_id' => $sample->id,
        ]);

        $queue->disciplina   = strtoupper((string) $sample->disciplina);
        $queue->status       = MlTrainingQueueItem::STATUS_QUEUED;
        $queue->approved_by  = $userId;
        $queue->approved_at  = Carbon::now();
        $queue->last_error   = null;

        $queue->save();
    }

    public function rejectSample(MlFeedbackSample $sample, ?int $userId = null): void
    {
        // reprovado mantém fora da fila
        $sample->status = MlFeedbackSample::STATUS_REPROVADO;
        $sample->save();

        // se existir na fila, opcional: remover ou marcar failed
        $queue = MlTrainingQueueItem::where('ml_feedback_sample_id', $sample->id)->first();
        if ($queue) {
            // você pode deletar ou só marcar como FAILED:
            $queue->status = MlTrainingQueueItem::STATUS_FAILED;
            $queue->last_error = 'Reprovado pelo treinador';
            $queue->save();
        }
    }
}
