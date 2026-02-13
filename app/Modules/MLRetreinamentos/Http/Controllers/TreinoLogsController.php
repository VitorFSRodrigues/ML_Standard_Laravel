<?php

namespace App\Modules\MLRetreinamentos\Http\Controllers;

use App\Http\Controllers\Controller;

class TreinoLogsController extends Controller
{
    public function index()
    {
        return view('mlretreinamentos::treino-logs.index');
    }
}
