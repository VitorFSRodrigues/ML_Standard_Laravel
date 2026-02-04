<?php

use App\Http\Controllers\AcervoController;
use App\Http\Controllers\FasesController;
use App\Http\Controllers\MlAprovacaoController;
use App\Http\Controllers\ModelosMlController;
use App\Http\Controllers\OrcamentistaController;
use App\Http\Controllers\OrcMLstdController;
use App\Http\Controllers\PerguntasController;
use App\Http\Controllers\PipedriveWebhookController;
use App\Http\Controllers\RequisitosController;
use App\Http\Controllers\StdELEController;
use App\Http\Controllers\StdTUBController;
use App\Http\Controllers\TriagemController;
use App\Http\Controllers\VarreduraController;
use App\Http\Controllers\DicionariosController;
use App\Http\Controllers\TreinoLogsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TriagemPerguntaController;
use App\Services\PipedriveService;
use App\Http\Controllers\TriagemSyncController;

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

Route::redirect('/', '/home');
Route::view('/home', 'home')->name('home'); // tela vazia
Route::view('/clientes-orcamentos', 'clientes.index')->name('clientes.index');

Route::resource('triagem', TriagemController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy'])
    ->names([
        'index'   => 'triagem.index',
        'show'    => 'triagem.show',
        'store'   => 'triagem.store',
        'update'  => 'triagem.update',
        'destroy' => 'triagem.destroy',
    ]);

Route::resource('perguntas', PerguntasController::class)
    ->names([
        'index'   => 'perguntas.index',
        'create'  => 'perguntas.create',
        'store'   => 'perguntas.store',
        'show'    => 'perguntas.show',
        'edit'    => 'perguntas.edit',
        'update'  => 'perguntas.update',
        'destroy' => 'perguntas.destroy',
    ]);    

// lista / view (opcional, se for usar)
Route::get('/triagem/{triagem}/respostas', [TriagemPerguntaController::class, 'index'])
    ->name('triagem.respostas.index');

// inicializa vínculos (gera linhas faltantes)
Route::post('/triagem/{triagem}/respostas/init', [TriagemPerguntaController::class, 'init'])
    ->name('triagem.respostas.init');

// upsert em lote
Route::post('/triagem/{triagem}/respostas', [TriagemPerguntaController::class, 'storeMany'])
    ->name('triagem.respostas.storeMany');

// update/destroy individuais
Route::put('/triagem-pergunta/{triagemPergunta}', [TriagemPerguntaController::class, 'update'])
    ->name('triagem.respostas.update');

Route::delete('/triagem-pergunta/{triagemPergunta}', [TriagemPerguntaController::class, 'destroy'])
    ->name('triagem.respostas.destroy');

Route::get('/requisitos', [RequisitosController::class, 'index'])
    ->name('requisitos.index');

Route::get('/orcamentista',                [OrcamentistaController::class, 'index'])->name('orcamentista.index');
Route::get('/orcamentista/{orcamentista}', [OrcamentistaController::class, 'show'])->name('orcamentista.show');

Route::get('/acervo', [AcervoController::class, 'index'])->name('acervo.index');

Route::get('/orcamentista/{orcamentista}/fases', [FasesController::class, 'index'])
    ->name('orcamentista.fases');

// Rota para testar se api Pipedrive está puxando corretamente os valores
Route::get('/pipedrive/test/{dealId}', function (int $dealId, PipedriveService $svc) {
    $deal = $svc->getDeal($dealId);
    if (!$deal) {
        return response('Deal não encontrado.', 404);
    }

    $triagemMap = $svc->mapDealToTriagem($deal);

    $orgMap = null;
    if (!empty($triagemMap['cliente_id']) && $triagemMap['cliente_id'] > 0) {
        $org = $svc->getOrganization($triagemMap['cliente_id']); // já é int normalizado
        $orgMap = $svc->mapOrgToCliente($org);
    }

    return response()->json([
        'raw_deal'       => $deal,
        'triagem_mapped' => $triagemMap,
        'org_mapped'     => $orgMap,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Route::post('/webhooks/pipedrive', [PipedriveWebhookController::class, 'handle'])
    ->name('webhooks.pipedrive');

Route::post('/triagem/sync', [TriagemSyncController::class, 'store'])
    ->name('triagem.sync')
    ->middleware(['auth']); // ajuste os middlewares que você usa

Route::get('/orcMLstd', [OrcMLstdController::class, 'index'])
    ->name('orcMLstd.index');

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
