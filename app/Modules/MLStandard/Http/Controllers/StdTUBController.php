<?php

namespace App\Modules\MLStandard\Http\Controllers;

use App\Http\Controllers\Controller;

class StdTUBController extends Controller
{
    public function index()
    {
        return view('mlstandard::stdtub.index');
    }
}
