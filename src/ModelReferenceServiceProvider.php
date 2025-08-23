<?php

declare(strict_types=1);

namespace MoSaid\ModelReference;

use MoSaid\ModelReference\Commands\GenerateCommand;
use MoSaid\ModelReference\Commands\InstallCommand;
use MoSaid\ModelReference\Commands\ModelReferenceCommand;
use MoSaid\ModelReference\Commands\RegenerateCommand;
use MoSaid\ModelReference\Commands\StatsCommand;
use MoSaid\ModelReference\Commands\ValidateCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModelReferenceServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('model-reference')
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
        
        $this->app->alias(ModelReference::class, 'model-reference');
    }

    public function boot(): void
    {
        parent::boot();
        
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../config/model-reference.php') => config_path('model-reference.php'),
            ], 'model-reference-config');
        }
    }
}