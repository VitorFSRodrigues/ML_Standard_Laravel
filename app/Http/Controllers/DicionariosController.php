<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DicionariosController extends Controller
{
    public function index(): View
    {
        return view('dicionarios.index');
    }
}
