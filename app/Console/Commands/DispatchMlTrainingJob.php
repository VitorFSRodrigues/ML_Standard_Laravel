<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendMlTrainingBatchJob;

class DispatchMlTrainingJob extends Command
{
    protected $signature = 'ml:send-training {--batch=50}';
    protected $description = 'Envia batch de itens aprovados para retreino';

    public function handle(): int
    {
        $batch = (int) $this->option('batch');

        SendMlTrainingBatchJob::dispatch($batch);

        $this->info("Job disparado com batch={$batch}");
        return self::SUCCESS;
    }
}
