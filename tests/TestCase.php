<?php

abstract class TestCase extends Orchestra\Testbench\TestCase
{
    protected $consoleOutput;

    protected function getPackageProviders($app)
    {
        return [\Themsaid\Langman\LangmanServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('langman.path', __DIR__.'/temp');
        $app['config']->set('view.paths', [__DIR__.'/views_temp']);
    }

    public function setUp()
    {
        parent::setUp();

        array_map('unlink', glob(__DIR__.'/temp/*/*'));
        array_map('rmdir', glob(__DIR__.'/temp/*'));
    }

    public function tearDown()
    {
        parent::tearDown();

        array_map('unlink', glob(__DIR__.'/temp/*/*'));
        array_map('rmdir', glob(__DIR__.'/temp/*'));

        $this->consoleOutput = '';
    }

    public function createTempFiles($files = [])
    {
        foreach ($files as $lang => $langFiles) {
            mkdir(__DIR__.'/temp/'.$lang);

            foreach ($langFiles as $file => $content) {
                file_put_contents(__DIR__.'/temp/'.$lang.'/'.$file.'.php', $content);
            }
        }
    }

    public function resolveApplicationConsoleKernel($app)
    {
        $app->singleton('artisan', function ($app) {
            return new \Illuminate\Console\Application($app, $app['events'], $app->version());
        });

        $app->singleton('Illuminate\Contracts\Console\Kernel', Kernel::class);
    }

    public function consoleOutput()
    {
        return $this->consoleOutput ?: $this->consoleOutput = $this->app[Kernel::class]->output();
    }
}