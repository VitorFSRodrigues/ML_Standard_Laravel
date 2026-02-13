<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rotas legadas foram removidas para manter apenas os modulos:
| - ML Standard
| - ML Retreinamentos
|
| As rotas desses modulos sao carregadas pelos respectivos ServiceProviders
| em app/Modules.
|
*/

Route::redirect('/', '/ml-standard/orcamentos');

Route::get('/home', function () {
    return redirect()->route('mlstandard.orcamentos.index');
})->name('home');
