<?php

namespace App\Modules\MLStandard\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class MLStandardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'mlstandard');
        $this->registerLivewireComponents();

        $migrationsPath = __DIR__ . '/../Database/Migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    private function registerLivewireComponents(): void
    {
        $components = [
            'powergrid.orc-mlstd-table' => \App\Modules\MLStandard\Livewire\Powergrid\OrcMLstdTable::class,
            'powergrid.levantamento-ele-table' => \App\Modules\MLStandard\Livewire\Powergrid\LevantamentoELETable::class,
            'powergrid.levantamento-tub-table' => \App\Modules\MLStandard\Livewire\Powergrid\LevantamentoTUBTable::class,
            'powergrid.std-ele-table' => \App\Modules\MLStandard\Livewire\Powergrid\StdEleTable::class,
            'powergrid.std-tub-table' => \App\Modules\MLStandard\Livewire\Powergrid\StdTUBTable::class,
            'powergrid.evidencias-uso-ml-table' => \App\Modules\MLStandard\Livewire\Powergrid\EvidenciasUsoMlTable::class,
            'modal.orcmlstd.create-edit' => \App\Modules\MLStandard\Livewire\Modal\OrcMLstd\CreateEdit::class,
            'modal.orcmlstd.confirm-delete' => \App\Modules\MLStandard\Livewire\Modal\OrcMLstd\ConfirmDelete::class,
            'modal.orc-mlstd.run-ml' => \App\Modules\MLStandard\Livewire\Modal\OrcMLstd\RunML::class,
            'modal.std-ele.create-edit' => \App\Modules\MLStandard\Livewire\Modal\StdELE\CreateEdit::class,
            'modal.std-ele.confirm-delete' => \App\Modules\MLStandard\Livewire\Modal\StdELE\ConfirmDelete::class,
            'modal.std-ele.import' => \App\Modules\MLStandard\Livewire\Modal\StdELE\Import::class,
            'modal.std-tub.create-edit' => \App\Modules\MLStandard\Livewire\Modal\StdTUB\CreateEdit::class,
            'modal.std-tub.confirm-delete' => \App\Modules\MLStandard\Livewire\Modal\StdTUB\ConfirmDelete::class,
            'modal.std-tub.import' => \App\Modules\MLStandard\Livewire\Modal\StdTUB\Import::class,
        ];

        foreach ($components as $alias => $class) {
            Livewire::component($alias, $class);
        }
    }
}
