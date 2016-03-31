<?php

use Illuminate\Contracts\Console\Kernel;

abstract class TestCase extends Orchestra\Testbench\TestCase
{
    protected $consoleOutput;

    protected function getPackageProviders($app)
    {
        return [\Themsaid\LangMan\LangManServiceProvider::class];
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->consoleOutput = '';
    }

    public function createTempFiles($files = [])
    {
        array_map('unlink', glob(__DIR__.'/temp/*/*'));
        array_map('rmdir', glob(__DIR__.'/temp/*'));

        foreach ($files as $lang => $langFiles) {
            mkdir(__DIR__.'/temp/'.$lang);

            foreach ($langFiles as $file => $content) {
                file_put_contents(__DIR__.'/temp/'.$lang.'/'.$file.'.php', $content);
            }
        }
    }

    public function consoleOutput()
    {
        return $this->consoleOutput ?: $this->consoleOutput = $this->app[Kernel::class]->output();
    }
}