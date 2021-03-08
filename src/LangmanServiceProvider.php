<?php

namespace Muathye\Themsaid\Langman;

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
            \Muathye\Themsaid\Langman\Commands\MissingCommand::class,
            \Muathye\Themsaid\Langman\Commands\RemoveCommand::class,
            \Muathye\Themsaid\Langman\Commands\TransCommand::class,
            \Muathye\Themsaid\Langman\Commands\ShowCommand::class,
            \Muathye\Themsaid\Langman\Commands\FindCommand::class,
            \Muathye\Themsaid\Langman\Commands\SyncCommand::class,
            \Muathye\Themsaid\Langman\Commands\RenameCommand::class,
        ]);
    }
}
