<?php

namespace App\Jobs;

use App\Modules\MLRetreinamentos\Models\MlTrainingQueueItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class SendMlTrainingBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $batchSize = 50
    ) {}

    public function handle(): void
    {
        $items = MlTrainingQueueItem::query()
            ->where('status', MlTrainingQueueItem::STATUS_QUEUED)
            ->orderBy('updated_at')
            ->limit($this->batchSize)
            ->with('sample') // pega o MlFeedbackSample junto
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        // monta payload de treino
        $payload = $items->map(function ($q) {
            $s = $q->sample;

            return [
                'disciplina' => $q->disciplina,
                'descricao'  => $s->descricao_original,
                'ml_pred'    => $s->ml_pred_json,
                'ml_prob'    => $s->ml_prob_str,
                'user_final' => $s->user_final_json,
                'edited_fields' => $s->edited_fields_json,
                'reason'     => $s->reason,
                'orc_ml_std_id' => $s->orc_ml_std_id,
                'ordem'      => $s->ordem,
                'sample_id'  => $s->id,
            ];
        })->values()->all();

        $baseUrl = config('services.ml_api.url', 'http://127.0.0.1:8001');
        $endpoint = '/TreinoML'; // ✅ você define na sua API depois

        try {
            $res = Http::timeout(120)->post($baseUrl . $endpoint, [
                'items' => $payload,
            ]);

            if (!$res->ok()) {
                throw new \RuntimeException('API retornou erro: ' . $res->status());
            }

            // sucesso -> marca SENT
            foreach ($items as $q) {
                $q->status  = MlTrainingQueueItem::STATUS_SENT;
                $q->sent_at = Carbon::now();
                $q->sent_by = auth()->id() ?? null; // pode ser null se job rodar sem sessão
                $q->last_error = null;
                $q->save();
            }

        } catch (\Throwable $e) {
            foreach ($items as $q) {
                $q->status = MlTrainingQueueItem::STATUS_FAILED;
                $q->last_error = $e->getMessage();
                $q->save();
            }
        }
    }
}
