<?php

declare(strict_types=1);

namespace LongAoDai\Repository;

use Illuminate\Support\ServiceProvider;
use LongAoDai\Repository\Console\CreatePatternCommand;

/**
 * Class RepositoryServiceProvider
 *
 * @package LongAoDai\Repository
 * @author  vochilong<vochilong.work@gmail.com>
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * Here we merge the default config so the package works
     * even if the user forgets to publish the config file.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pattern.php',
            'pattern'
        );
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Allow publishing config file
        $this->publishes([
            __DIR__ . '/../config/pattern.php' => config_path('pattern.php'),
        ], 'laravel-repository');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreatePatternCommand::class,
            ]);
        }
    }
}
