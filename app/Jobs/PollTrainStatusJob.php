<?php

namespace App\Jobs;

use App\Models\ModeloMl;
use App\Models\Varredura;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TreinoLog;

class PollTrainStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 120; // ~20min se delay=10s

    public function __construct(
        public string $jobId,
        public int $varreduraId,
        public int $revisaoEle,
        public int $revisaoTub
    ) {}

    public function handle(): void
    {
        $baseUrl = config('services.ml_api.url', 'http://127.0.0.1:8001');
        $url = rtrim($baseUrl, '/') . '/train/' . $this->jobId;
        $attempt = $this->attempts();

        Log::info('PollTrainStatusJob attempt', [
            'job_id' => $this->jobId,
            'varredura_id' => $this->varreduraId,
            'attempt' => $attempt,
        ]);

        try {
            $response = Http::timeout(30)->get($url);
        } catch (\Throwable $e) {
            $this->rescheduleOrFail('Erro ao consultar status do treino.');
            return;
        }

        if (!$response->ok()) {
            $this->rescheduleOrFail('Falha HTTP ao consultar status do treino.');
            return;
        }

        $payload = $response->json() ?? [];
        $status = strtolower((string) ($payload['status'] ?? 'running'));
        $status = $status !== '' ? $status : 'running';

        Log::info('PollTrainStatusJob status', [
            'job_id' => $this->jobId,
            'status' => $status,
            'attempt' => $attempt,
        ]);

        $this->updateVarreduraStatus($status);

        $this->createLog(
            $status,
            $this->buildLogMessage($status, $attempt),
            $this->buildLogPayload($payload, $status)
        );

        if (in_array($status, ['queued', 'running'], true)) {
            $this->rescheduleOrFail(null);
            return;
        }

        if ($status === 'failed') {
            Log::warning('PollTrainStatusJob failed', [
                'job_id' => $this->jobId,
                'error' => $payload['error'] ?? null,
                'attempt' => $attempt,
            ]);
            $this->updateModelsFailure($payload);
            return;
        }

        if ($status === 'completed') {
            Log::info('PollTrainStatusJob completed', [
                'job_id' => $this->jobId,
                'attempt' => $attempt,
            ]);
            $this->updateModelsSuccess($payload);
        }
    }

    private function updateVarreduraStatus(string $status): void
    {
        Varredura::query()
            ->where('id', $this->varreduraId)
            ->update(['treino_status' => $status]);
    }

    private function updateModelsFailure(array $payload): void
    {
        $error = $payload['error'] ?? null;

        $this->upsertModelo('ELE', $this->revisaoEle, $payload, [
            'treino_status' => 'failed',
            'treino_error' => $error,
        ]);
        $this->upsertModelo('TUB', $this->revisaoTub, $payload, [
            'treino_status' => 'failed',
            'treino_error' => $error,
        ]);
    }

    private function updateModelsSuccess(array $payload): void
    {
        $result = $payload['result'] ?? [];

        $this->applyResult('ELE', $result['ELE'] ?? null, $payload, $this->revisaoEle);
        $this->applyResult('TUB', $result['TUB'] ?? null, $payload, $this->revisaoTub);
    }

    private function applyResult(string $disciplina, ?array $data, array $payload, int $fallbackRevisao): void
    {
        if (!$data) {
            $this->upsertModelo($disciplina, $fallbackRevisao, $payload, [
                'treino_status' => 'completed',
            ]);
            return;
        }

        $revisao = isset($data['revisao']) ? (int) $data['revisao'] : $fallbackRevisao;

        $this->upsertModelo($disciplina, $revisao, $payload, [
            'treino_status' => 'completed',
            'treino_data_at' => $data['data'] ?? null,
            'treino_exact_match_ratio' => $data['exact_match_ratio'] ?? null,
            'treino_n_samples' => $data['n_samples'] ?? null,
            'treino_n_train' => $data['n_train'] ?? null,
            'treino_n_test' => $data['n_test'] ?? null,
            'treino_classification_report' => $data['classification_report'] ?? null,
            'treino_error' => null,
            'acuracia' => $data['exact_match_ratio'] ?? null,
        ]);
    }

    private function upsertModelo(string $disciplina, int $revisao, array $payload, array $extra): void
    {
        if ($revisao <= 0) {
            return;
        }

        $base = [
            'data' => Carbon::now()->format('Y-m-d'),
            'treino_job_id' => $this->jobId,
            'treino_status' => $payload['status'] ?? null,
            'treino_created_at' => $payload['created_at'] ?? null,
            'treino_started_at' => $payload['started_at'] ?? null,
            'treino_finished_at' => $payload['finished_at'] ?? null,
        ];

        ModeloMl::updateOrCreate(
            [
                'disciplina' => $disciplina,
                'revisao' => $revisao,
            ],
            array_filter(array_merge($base, $extra), fn ($v) => $v !== null)
        );
    }

    private function rescheduleOrFail(?string $error): void
    {
        $attempt = $this->attempts();

        if ($error) {
            Log::warning('PollTrainStatusJob retry', [
                'job_id' => $this->jobId,
                'attempt' => $attempt,
                'error' => $error,
            ]);

            $this->createLog('error', $error, null);
        }

        if ($attempt >= $this->tries) {
            $this->updateVarreduraStatus('failed');
            $this->updateModelsFailure(['error' => $error]);
            return;
        }

        Log::info('PollTrainStatusJob rescheduled', [
            'job_id' => $this->jobId,
            'attempt' => $attempt,
            'delay_seconds' => 10,
        ]);

        $this->release(10);
    }

    private function createLog(string $status, ?string $message, ?array $payload): void
    {
        try {
            TreinoLog::create([
                'job_id' => $this->jobId,
                'varredura_id' => $this->varreduraId,
                'status' => $status,
                'message' => $message,
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('TreinoLog insert failed', [
                'job_id' => $this->jobId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            TreinoLog::create([
                'job_id' => $this->jobId,
                'varredura_id' => $this->varreduraId,
                'status' => 'error',
                'message' => 'Falha ao salvar log: ' . $e->getMessage(),
                'payload' => null,
            ]);
        }
    }

    private function buildLogMessage(string $status, int $attempt): string
    {
        return "Status: {$status} (tentativa {$attempt})";
    }

    private function buildLogPayload(array $payload, string $status): array
    {
        if ($status === 'completed' || $status === 'failed') {
            return $payload;
        }

        return array_intersect_key($payload, array_flip([
            'job_id',
            'status',
            'created_at',
            'started_at',
            'finished_at',
            'error',
        ]));
    }
}
