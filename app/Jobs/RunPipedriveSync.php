<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunPipedriveSync implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public ?string $since = null,  // ex: '2025-12-15 00:00:00' (opcional)
    ) {}

    public function handle(): void
    {
        $args = [];
        if ($this->since) {
            $args['--since'] = $this->since;
        }

        Log::info('RunPipedriveSync:start', $args);
        Artisan::call('pipedrive:sync', $args);
        Log::info('RunPipedriveSync:end', [
            'exitCode' => Artisan::output(),
        ]);
    }
}
