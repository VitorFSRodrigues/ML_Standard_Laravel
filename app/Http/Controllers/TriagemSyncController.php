<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\RunPipedriveSync;

class TriagemSyncController extends Controller
{
    public function store(Request $request)
    {
        // Opcional: permitir passar um "desde quando" pela UI
        $since = $request->string('since')->nullable();

        RunPipedriveSync::dispatch($since);

        return back()->with('status', 'Sincronização enfileirada. Você verá as novas entradas em alguns instantes.');
    }
}
