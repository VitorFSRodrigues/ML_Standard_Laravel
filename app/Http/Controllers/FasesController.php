<?php

namespace App\Http\Controllers;

use App\Models\Orcamentista;
use App\Models\Triagem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FasesController extends Controller
{
    public function index(Orcamentista $orcamentista, Request $request): View
    {
        // triagem alvo vem por query: /orcamentista/{id}/fases?triagemId=123
        $triagemId = (int) $request->query('triagemId', 0);
        $triagem   = $triagemId ? Triagem::with(['clienteFinal'])->find($triagemId) : null;

        return view('orcamentista.fases', compact('orcamentista', 'triagemId', 'triagem'));
    }
}
