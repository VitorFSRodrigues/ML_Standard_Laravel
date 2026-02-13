<?php

namespace App\Modules\MLRetreinamentos\Http\Controllers;

use App\Http\Controllers\Controller;

class MlAprovacaoController extends Controller
{
    public function index()
    {
        return view('mlretreinamentos::aprovacao.index');
    }
}
