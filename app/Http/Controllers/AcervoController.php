<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AcervoController extends Controller
{
    public function index(): View
    {
        return view('acervo.index');
    }
}
