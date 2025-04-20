<?php

namespace MoSaid\ModelReference;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use MoSaid\ModelReference\Commands\ModelReferenceCommand;

class ModelReferenceServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('model-reference')
            ->hasConfigFile();
//            ->hasViews()
//            ->hasMigration('create_model_reference_table')
//            ->hasCommand(ModelReferenceCommand::class);
    }
}
