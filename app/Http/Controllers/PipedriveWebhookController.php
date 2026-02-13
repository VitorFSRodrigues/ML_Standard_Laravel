<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\ImportDealFromPipedrive;
use App\Jobs\UpdateOrganizationFromPipedrive;

class PipedriveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->json()->all();
        Log::info('PIPEDRIVE WEBHOOK', ['headers' => $request->headers->all(), 'payload' => $payload]);

        $entity = data_get($payload, 'meta.entity');   // 'deal' | 'organization' | ...
        $action = data_get($payload, 'meta.action');   // 'create' | 'update' | 'merge' | ...

        if ($entity === 'deal') {
            $dealId = (int) data_get($payload, 'data.id');
            ImportDealFromPipedrive::dispatch($dealId);
        }

        if ($entity === 'organization') {
            $orgId = (int) data_get($payload, 'data.id');
            UpdateOrganizationFromPipedrive::dispatch($orgId, $action);
        }

        return response()->json(['ok' => true]);
    }

}
