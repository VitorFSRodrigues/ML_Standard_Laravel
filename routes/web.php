<?php

use App\Http\Controllers\DicionariosController;
use App\Http\Controllers\EvidenciasUsoMlController;
use App\Http\Controllers\MlAprovacaoController;
use App\Http\Controllers\ModelosMlController;
use App\Http\Controllers\OrcMLstdController;
use App\Http\Controllers\StdELEController;
use App\Http\Controllers\StdTUBController;
use App\Http\Controllers\TreinoLogsController;
use App\Http\Controllers\VarreduraController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/orcMLstd');

Route::get('/orcMLstd', [OrcMLstdController::class, 'index'])
    ->name('orcMLstd.index');

Route::get('/orcMLstd/evidencias-uso', [EvidenciasUsoMlController::class, 'index'])
    ->name('orc-mlstd.evidencias-uso.index');

Route::post('/orcMLstd/evidencias-uso/tempos', [EvidenciasUsoMlController::class, 'updateTempos'])
    ->name('orc-mlstd.evidencias-uso.tempos.update');

Route::get('/orcMLstd/{id}/levantamento', [OrcMLstdController::class, 'levantamento'])
    ->whereNumber('id')
    ->name('orc-mlstd.levantamento');

Route::get('/orcMLstd/template/levantamento', [OrcMLstdController::class, 'downloadTemplate'])
    ->name('orc-mlstd.template.levantamento');

Route::post('/orcMLstd/{id}/levantamento/import', [OrcMLstdController::class, 'importLevantamento'])
    ->name('orc-mlstd.import.levantamento');

Route::get('/orc-mlstd/{id}/export/levantamento', [OrcMLstdController::class, 'exportLevantamento'])
    ->name('orc-mlstd.export.levantamento');

Route::get('/StdELE', [StdELEController::class, 'index'])
    ->name('StdELE.index');

Route::get('/StdTUB', [StdTUBController::class, 'index'])
    ->name('std-tub.index');

Route::get('/aprovacao', [MlAprovacaoController::class, 'index'])
    ->name('ml.aprovacao.index');

Route::get('/modelos_ml', [ModelosMlController::class, 'index'])
    ->name('modelos-ml.index');

Route::get('/varredura', [VarreduraController::class, 'index'])
    ->name('varredura.index');

Route::get('/varredura/{varredura}', [VarreduraController::class, 'show'])
    ->whereNumber('varredura')
    ->name('varredura.show');

Route::get('/dicionarios', [DicionariosController::class, 'index'])
    ->name('dicionarios.index');

Route::get('/treino-logs', [TreinoLogsController::class, 'index'])
    ->name('treino-logs.index');
