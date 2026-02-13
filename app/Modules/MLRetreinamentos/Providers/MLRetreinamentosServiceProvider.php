<?php

namespace App\Modules\MLRetreinamentos\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class MLRetreinamentosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'mlretreinamentos');
        $this->registerLivewireComponents();

        $migrationsPath = __DIR__ . '/../Database/Migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    private function registerLivewireComponents(): void
    {
        $components = [
            'powergrid.aprovacao-ele-global-table' => \App\Modules\MLRetreinamentos\Livewire\Powergrid\AprovacaoELEGlobalTable::class,
            'powergrid.aprovacao-tub-global-table' => \App\Modules\MLRetreinamentos\Livewire\Powergrid\AprovacaoTUBGlobalTable::class,
            'powergrid.modelos-ml-table' => \App\Modules\MLRetreinamentos\Livewire\Powergrid\ModelosMlTable::class,
            'powergrid.varredura-table' => \App\Modules\MLRetreinamentos\Livewire\Powergrid\VarreduraTable::class,
            'powergrid.varredura-ele-table' => \App\Modules\MLRetreinamentos\Livewire\Powergrid\VarreduraELETable::class,
            'powergrid.varredura-tub-table' => \App\Modules\MLRetreinamentos\Livewire\Powergrid\VarreduraTUBTable::class,
            'powergrid.dicionarios-table' => \App\Modules\MLRetreinamentos\Livewire\Powergrid\DicionariosTable::class,
            'powergrid.treino-logs-table' => \App\Modules\MLRetreinamentos\Livewire\Powergrid\TreinoLogsTable::class,
            'modal.dicionarios.create-edit' => \App\Modules\MLRetreinamentos\Livewire\Modal\Dicionarios\CreateEdit::class,
            'modal.dicionarios.confirm-delete' => \App\Modules\MLRetreinamentos\Livewire\Modal\Dicionarios\ConfirmDelete::class,
            'modal.varredura.add-dict-item' => \App\Modules\MLRetreinamentos\Livewire\Modal\Varredura\AddDictItem::class,
            'modal.varredura.confirm-delete' => \App\Modules\MLRetreinamentos\Livewire\Modal\Varredura\ConfirmDelete::class,
            'modal.varredura.confirm-ready' => \App\Modules\MLRetreinamentos\Livewire\Modal\Varredura\ConfirmReady::class,
            'modal.varredura.create-edit' => \App\Modules\MLRetreinamentos\Livewire\Modal\Varredura\CreateEdit::class,
        ];

        foreach ($components as $alias => $class) {
            Livewire::component($alias, $class);
        }
    }
}
