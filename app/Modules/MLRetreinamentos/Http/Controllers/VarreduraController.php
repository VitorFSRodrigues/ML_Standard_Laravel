<?php

namespace App\Modules\MLRetreinamentos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MLRetreinamentos\Models\Varredura;

class VarreduraController extends Controller
{
    public function index()
    {
        return view('mlretreinamentos::varredura.index');
    }

    public function show(Varredura $varredura)
    {
        return view('mlretreinamentos::varredura.show', compact('varredura'));
    }
}
