<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ModelosMlController extends Controller
{
    public function index(): View
    {
        return view('modelos-ml.index');
    }
}
