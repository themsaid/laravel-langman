<?php

namespace OSSTools\Langman;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

class LangmanServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/langman.php' => config_path('langman.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/langman.php', 'langman');

        $this->app->bind(Manager::class, function () {
            return new Manager(
                new Filesystem,
                $this->app['config']['langman.path'],
                array_merge($this->app['config']['view.paths'], [$this->app['path']])
            );
        });

        $this->commands([
            \OSSTools\Langman\Commands\MissingCommand::class,
            \OSSTools\Langman\Commands\RemoveCommand::class,
            \OSSTools\Langman\Commands\TransCommand::class,
            \OSSTools\Langman\Commands\ShowCommand::class,
            \OSSTools\Langman\Commands\FindCommand::class,
            \OSSTools\Langman\Commands\SyncCommand::class,
            \OSSTools\Langman\Commands\RenameCommand::class,
        ]);
    }
}
