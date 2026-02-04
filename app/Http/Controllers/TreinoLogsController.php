<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TreinoLogsController extends Controller
{
    public function index(): View
    {
        return view('treino-logs.index');
    }
}
