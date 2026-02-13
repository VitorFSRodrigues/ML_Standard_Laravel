<?php

namespace App\Modules\MLStandard\Http\Controllers;

use App\Http\Controllers\Controller;

class StdELEController extends Controller
{
    public function index()
    {
        return view('mlstandard::stdele.index');
    }
}
