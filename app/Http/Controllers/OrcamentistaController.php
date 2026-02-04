<?php

namespace App\Http\Controllers;

use App\Models\Orcamentista;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrcamentistaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('orcamentista.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Orcamentista $orcamentista)
    {
        return view('orcamentista.show', compact('orcamentista'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Orcamentista $orcamentista)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Orcamentista $orcamentista)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Orcamentista $orcamentista)
    {
        //
    }
}
