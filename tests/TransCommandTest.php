<?php

use Mockery as m;
use Themsaid\Langman\Manager;

class TransCommandTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('langman.path', __DIR__.'/temp');
    }

    public function testCommandErrorOutputOnMissingKey()
    {
        $this->createTempFiles();

        $this->artisan('langman:trans', ['key' => 'users']);

        $this->assertContains('Could not recognize the key you want to translate.', $this->consoleOutput());
    }

    public function testCommandAsksForConfirmationToCreateFileIfNotFound()
    {
        $this->createTempFiles();
        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\LangMan\Commands\TransCommand[confirm]', [$manager]);
        $command->shouldReceive('confirm')->once()->andReturn(false);

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name']);
    }

    public function testCommandExitsWhenFileNotFoundAndConfirmationFalse()
    {
        $this->createTempFiles(['en' => []]);

        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\Langman\Commands\TransCommand[confirm]', [$manager]);
        $command->shouldReceive('confirm')->once()->andReturn(false);

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name']);

        $this->assertFileNotExists($this->app['config']['langman.path'].'/en/users.php');
    }

    public function testCommandCreatesFileIfNotFoundWhenConfirmed()
    {
        $this->createTempFiles(['en' => []]);

        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\Langman\Commands\TransCommand[confirm]', [$manager]);
        $command->shouldReceive('confirm')->once()->andReturn(true);

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name']);

        $this->assertFileExists($this->app['config']['langman.path'].'/en/users.php');
    }

    public function testCommandAsksForValuePerLanguageAndWriteToFile()
    {
        $this->createTempFiles([
            'en' => ['users' => "<?php\n return [];"],
            'nl' => ['users' => "<?php\n return [];"],
        ]);

        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\Langman\Commands\TransCommand[ask]', [$manager]);
        $command->shouldReceive('confirm')->never();
        $command->shouldReceive('ask')->with('users.name.en translation:', '')->andReturn('name');
        $command->shouldReceive('ask')->with('users.name.nl translation:', '')->andReturn('naam');

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name']);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $nlFile = (array) include $this->app['config']['langman.path'].'/nl/users.php';
        $this->assertEquals('name', $enFile['name']);
        $this->assertEquals('naam', $nlFile['name']);
    }

    public function testCommandAsksForValuePerLanguageAndWriteToFileUpdatingExisting()
    {
        $this->createTempFiles([
            'en' => ['users' => "<?php\n return ['name' => 'nil'];"],
            'nl' => ['users' => "<?php\n return [];"],
        ]);

        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\Langman\Commands\TransCommand[ask]', [$manager]);
        $command->shouldReceive('confirm')->never();
        $command->shouldReceive('ask')->with('users.name.en translation (updating):', 'nil')->andReturn('name');
        $command->shouldReceive('ask')->with('users.name.nl translation:', '')->andReturn('naam');

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name']);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $nlFile = (array) include $this->app['config']['langman.path'].'/nl/users.php';
        $this->assertEquals('name', $enFile['name']);
        $this->assertEquals('naam', $nlFile['name']);
    }
}
