<?php

namespace LongAoDai\Repository;

use Illuminate\Support\ServiceProvider;
use LongAoDai\Repository\Console\CreatePatternCommand;

/**
 * Class RepositoryServiceProvider
 *
 * @package LongAoDai\Repository
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/pattern.php' => config_path('pattern.php'),
        ], 'longaodai-pattern');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreatePatternCommand::class,
            ]);
        }
    }
}
