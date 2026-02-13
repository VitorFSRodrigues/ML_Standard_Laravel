<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $allowlist = collect(config('modules.allowlist', []))
            ->map(fn (mixed $module): string => trim((string) $module))
            ->filter()
            ->values();

        if ($allowlist->isEmpty()) {
            return;
        }

        $discoveredProviders = $this->discoverModuleProviders();
        $moduleConfig = config('modules.modules', []);

        foreach ($allowlist as $moduleName) {
            $enabled = (bool) data_get($moduleConfig, "{$moduleName}.enabled", true);

            if (! $enabled) {
                continue;
            }

            $providerClass = $discoveredProviders[$moduleName] ?? null;

            if (! $providerClass || ! class_exists($providerClass)) {
                continue;
            }

            $this->app->register($providerClass);
        }
    }

    /**
     * @return array<string, string>
     */
    private function discoverModuleProviders(): array
    {
        $modulesPath = app_path('Modules');
        if (! is_dir($modulesPath)) {
            return [];
        }

        $providers = [];

        foreach (File::directories($modulesPath) as $moduleDir) {
            $moduleName = basename($moduleDir);
            $providerDir = $moduleDir . DIRECTORY_SEPARATOR . 'Providers';

            if (! is_dir($providerDir)) {
                continue;
            }

            foreach (File::files($providerDir) as $providerFile) {
                $classBase = pathinfo($providerFile->getFilename(), PATHINFO_FILENAME);

                if (! Str::endsWith($classBase, 'ServiceProvider')) {
                    continue;
                }

                $className = "App\\Modules\\{$moduleName}\\Providers\\{$classBase}";
                if (class_exists($className)) {
                    $providers[$moduleName] = $className;
                    break;
                }
            }
        }

        return $providers;
    }
}
