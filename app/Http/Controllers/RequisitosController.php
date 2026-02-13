<?php

namespace App\Http\Controllers;

use App\Models\Requisito;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequisitosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('requisitos.index');
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
    public function show(Requisito $requisito)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Requisito $requisito)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Requisito $requisito)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Requisito $requisito)
    {
        //
    }
}
