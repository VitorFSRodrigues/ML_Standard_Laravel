<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class StdELEController extends Controller
{
    public function index(): View
    {
        return view('stdele.index');
    }
}
