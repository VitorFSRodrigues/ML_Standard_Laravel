<?php

namespace App\Console\Commands;

use App\Jobs\ImportDealFromPipedrive;
use App\Services\PipedriveService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncPipedriveDeals extends Command
{
    protected $signature   = 'pipedrive:sync {--since=}';
    protected $description = 'Puxa deals recentes/atualizados do Pipedrive e enfileira a importação';

    public function handle(PipedriveService $svc): int
    {
        $sinceOpt = $this->option('since');
        $since    = $sinceOpt
            ? Carbon::parse($sinceOpt)
            : now()->subHours((int) config('pipedrive.sync.lookback_hours', 48));

        $queued = 0;

        foreach ($svc->listDealsUpdatedSince($since) as $deal) {
            if (!empty($deal['id'])) {
                ImportDealFromPipedrive::dispatch((int) $deal['id']);
                $queued++;
            }
        }

        $this->info("Queued {$queued} deal(s) since " . $since->toIso8601String());
        return self::SUCCESS;
    }
}
