<?php

namespace App\Modules\MLRetreinamentos\Http\Controllers;

use App\Http\Controllers\Controller;

class DicionariosController extends Controller
{
    public function index()
    {
        return view('mlretreinamentos::dicionarios.index');
    }
}
