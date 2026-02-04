<?php

namespace App\Http\Controllers;

use App\Models\Varredura;
use Illuminate\View\View;

class VarreduraController extends Controller
{
    public function index(): View
    {
        return view('varredura.index');
    }

    public function show(Varredura $varredura): View
    {
        return view('varredura.show', compact('varredura'));
    }
}
