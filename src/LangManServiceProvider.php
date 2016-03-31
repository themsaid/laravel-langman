<?php

namespace Themsaid\LangMan;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class LangManServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Manager::class, function () {
            return new Manager(
                new Filesystem(),
                $this->app['config']['langman.path']
            );
        });

        $this->commands([
            \Themsaid\LangMan\Commands\ShowCommand::class,
            \Themsaid\LangMan\Commands\FindCommand::class,
        ]);
    }
}