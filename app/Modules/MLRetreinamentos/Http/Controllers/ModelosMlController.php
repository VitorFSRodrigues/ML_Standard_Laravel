<?php

namespace App\Modules\MLRetreinamentos\Http\Controllers;

use App\Http\Controllers\Controller;

class ModelosMlController extends Controller
{
    public function index()
    {
        return view('mlretreinamentos::modelos-ml.index');
    }
}
