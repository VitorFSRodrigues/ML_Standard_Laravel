<?php

use App\Modules\MLRetreinamentos\Http\Controllers\DicionariosController;
use App\Modules\MLRetreinamentos\Http\Controllers\MlAprovacaoController;
use App\Modules\MLRetreinamentos\Http\Controllers\ModelosMlController;
use App\Modules\MLRetreinamentos\Http\Controllers\TreinoLogsController;
use App\Modules\MLRetreinamentos\Http\Controllers\VarreduraController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('ml-retreinamentos')
    ->name('mlretreinamentos.')
    ->group(function () {
        Route::get('/aprovacao', [MlAprovacaoController::class, 'index'])
            ->name('aprovacao.index');

        Route::get('/modelos', [ModelosMlController::class, 'index'])
            ->name('modelos.index');

        Route::get('/varredura', [VarreduraController::class, 'index'])
            ->name('varredura.index');

        Route::get('/varredura/{varredura}', [VarreduraController::class, 'show'])
            ->whereNumber('varredura')
            ->name('varredura.show');

        Route::get('/dicionarios', [DicionariosController::class, 'index'])
            ->name('dicionarios.index');

        Route::get('/treino-logs', [TreinoLogsController::class, 'index'])
            ->name('treino-logs.index');
    });
