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

        $this->removeTempFiles();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->removeTempFiles();

        $this->consoleOutput = '';
    }

    public function createTempFiles($files = [])
    {
        foreach ($files as $dir => $dirFiles) {
            mkdir(__DIR__.'/temp/'.$dir, 0777, true);

            foreach ($dirFiles as $file => $content) {
                if (is_array($content)) {
                    mkdir(__DIR__.'/temp/'.$dir.'/'.$file, 0777, true);

                    foreach ($content as $subDir => $subContent) {
                        mkdir(__DIR__.'/temp/vendor/'.$file.'/'.$subDir, 0777, true);
                        foreach ($subContent as $subFile => $subsubContent) {
                            file_put_contents(__DIR__.'/temp/'.$dir.'/'.$file.'/'.$subDir.'/'.$subFile.'.php', $subsubContent);
                        }
                    }
                } else {
                    $fileParts = explode('/', $file);
                    if (count($fileParts) > 1) {
                        $fileParts = array_slice($fileParts, 0, count($fileParts) - 1);
                    }
                    mkdir(__DIR__.'/temp/'.$dir.'/'.implode('/', $fileParts), 0777, true);
                    file_put_contents(__DIR__.'/temp/'.$dir.'/'.$file.'.php', $content);
                }
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

    public function artisan($command, $parameters = [])
    {
        parent::artisan($command, array_merge($parameters, ['--no-interaction' => true]));
    }

    public function consoleOutput()
    {
        return $this->consoleOutput ?: $this->consoleOutput = $this->app[Kernel::class]->output();
    }

    private function removeTempFiles() {
        $this->rrmdir(__DIR__.'/temp', '/\.gitignore$/i',  true);
    }

    private function rrmdir($dir, $ignoreRegex, $skipTopLevel = false) {
        if (is_dir($dir)) {
          $objects = scandir($dir);
          foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
              if (is_dir($dir.DIRECTORY_SEPARATOR.$object)) {
                $this->rrmdir($dir.DIRECTORY_SEPARATOR.$object, $ignoreRegex);
              } else if (empty($ignoreRegex) || empty(preg_match($ignoreRegex, $object))) {
                unlink($dir.DIRECTORY_SEPARATOR.$object);
              }
            }
          }
          if (empty($skipTopLevel)) {
            rmdir($dir);
          }
        }
    }
}
