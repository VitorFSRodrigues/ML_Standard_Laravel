<?php

use App\Modules\MLStandard\Http\Controllers\EvidenciasUsoMlController;
use App\Modules\MLStandard\Http\Controllers\OrcMLstdController;
use App\Modules\MLStandard\Http\Controllers\StdELEController;
use App\Modules\MLStandard\Http\Controllers\StdTUBController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('ml-standard')
    ->name('mlstandard.')
    ->group(function () {
        Route::get('/orcamentos', [OrcMLstdController::class, 'index'])
            ->name('orcamentos.index');

        Route::get('/orcamentos/evidencias-uso', [EvidenciasUsoMlController::class, 'index'])
            ->name('orcamentos.evidencias-uso.index');

        Route::post('/orcamentos/evidencias-uso/tempos', [EvidenciasUsoMlController::class, 'updateTempos'])
            ->name('orcamentos.evidencias-uso.tempos.update');

        Route::get('/orcamentos/{id}/levantamento', [OrcMLstdController::class, 'levantamento'])
            ->whereNumber('id')
            ->name('orcamentos.levantamento');

        Route::get('/orcamentos/template/levantamento', [OrcMLstdController::class, 'downloadTemplate'])
            ->name('orcamentos.template.levantamento');

        Route::post('/orcamentos/{id}/levantamento/import', [OrcMLstdController::class, 'importLevantamento'])
            ->whereNumber('id')
            ->name('orcamentos.import.levantamento');

        Route::get('/orcamentos/{id}/export/levantamento', [OrcMLstdController::class, 'exportLevantamento'])
            ->whereNumber('id')
            ->name('orcamentos.export.levantamento');

        Route::get('/std-ele', [StdELEController::class, 'index'])
            ->name('std-ele.index');

        Route::get('/std-tub', [StdTUBController::class, 'index'])
            ->name('std-tub.index');
    });
