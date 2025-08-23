<?php

declare(strict_types=1);

namespace MohamedSaid\Referenceable;

use MohamedSaid\Referenceable\Commands\GenerateCommand;
use MohamedSaid\Referenceable\Commands\InstallCommand;
use MohamedSaid\Referenceable\Commands\ModelReferenceCommand;
use MohamedSaid\Referenceable\Commands\RegenerateCommand;
use MohamedSaid\Referenceable\Commands\StatsCommand;
use MohamedSaid\Referenceable\Commands\ValidateCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModelReferenceServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('referenceable')
            ->hasConfigFile()
            ->hasCommands([
                ModelReferenceCommand::class,
                InstallCommand::class,
                GenerateCommand::class,
                ValidateCommand::class,
                RegenerateCommand::class,
                StatsCommand::class,
            ]);
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(ModelReference::class, function ($app) {
            return new ModelReference();
        });

        $this->app->alias(ModelReference::class, 'referenceable');
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../config/referenceable.php') => config_path('referenceable.php'),
            ], 'referenceable-config');
        }
    }
}
