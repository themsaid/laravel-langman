<?php

use Mockery as m;
use Themsaid\Langman\Manager;

class TransCommandTest extends TestCase
{
    public function testCommandErrorOutputOnMissingKey()
    {
        $this->createTempFiles();

        $this->artisan('langman:trans', ['key' => 'users']);

        $this->assertContains('Could not recognize the key you want to translate.', $this->consoleOutput());
    }

    public function testCommandErrorOutputOnLanguageNotFound()
    {
        $this->createTempFiles(['en' => ['users' => '']]);

        $this->artisan('langman:trans', ['key' => 'users.name:sd']);

        $this->assertContains('Language (sd) could not be found!', $this->consoleOutput());
    }

    public function testCommandAsksForConfirmationToCreateFileIfNotFound()
    {
        $this->createTempFiles();
        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\Langman\Commands\TransCommand[confirm]', [$manager]);
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
        $command->shouldReceive('ask')->once()->with('users.name:en translation', null)->andReturn('name');
        $command->shouldReceive('ask')->once()->with('users.name:nl translation', null)->andReturn('naam');

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name']);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $nlFile = (array) include $this->app['config']['langman.path'].'/nl/users.php';
        $this->assertEquals('name', $enFile['name']);
        $this->assertEquals('naam', $nlFile['name']);
    }

    public function testCommandAsksForValuePerLanguageAndUpdatingExistingInFile()
    {
        $this->createTempFiles([
            'en' => ['users' => "<?php\n return ['name' => 'nil'];"],
            'nl' => ['users' => "<?php\n return [];"],
        ]);

        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\Langman\Commands\TransCommand[ask]', [$manager]);
        $command->shouldReceive('confirm')->never();
        $command->shouldReceive('ask')->once()->with('users.name:en translation', 'nil')->andReturn('name');
        $command->shouldReceive('ask')->once()->with('users.name:nl translation', '')->andReturn('naam');

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name']);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $nlFile = (array) include $this->app['config']['langman.path'].'/nl/users.php';
        $this->assertEquals('name', $enFile['name']);
        $this->assertEquals('naam', $nlFile['name']);
    }

    public function testCommandAsksForValueForOnlyProvidedLanguage()
    {
        $this->createTempFiles([
            'en' => ['users' => "<?php\n return [];"],
            'nl' => ['users' => "<?php\n return [];"],
        ]);

        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\Langman\Commands\TransCommand[ask]', [$manager]);
        $command->shouldReceive('confirm')->never();
        $command->shouldReceive('ask')->once()->with('users.name:en translation', null)->andReturn('name');

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name:en']);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $this->assertEquals('name', $enFile['name']);
    }

    public function testCommandAsksForValuePerLanguageForNestedKeysAndWriteFile()
    {
        $this->createTempFiles([
            'en' => ['users' => "<?php\n return [];"],
            'nl' => ['users' => "<?php\n return [];"],
        ]);

        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\Langman\Commands\TransCommand[ask]', [$manager]);
        $command->shouldReceive('confirm')->never();
        $command->shouldReceive('ask')->once()->with('users.name.first:en translation', null)->andReturn('name');
        $command->shouldReceive('ask')->once()->with('users.name.first:nl translation', null)->andReturn('naam');

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name.first']);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $nlFile = (array) include $this->app['config']['langman.path'].'/nl/users.php';
        $this->assertEquals(['first' => 'name'], $enFile['name']);
        $this->assertEquals(['first' => 'naam'], $nlFile['name']);
    }

    public function testCommandAsksForLanguageForNestedKeysAndWriteFile()
    {
        $this->createTempFiles([
            'en' => ['users' => "<?php\n return [];"],
            'nl' => ['users' => "<?php\n return [];"],
        ]);

        $manager = $this->app[Manager::class];
        $command = m::mock('\Themsaid\Langman\Commands\TransCommand[ask]', [$manager]);
        $command->shouldReceive('confirm')->never();
        $command->shouldReceive('ask')->once()->with('users.name.first:en translation', null)->andReturn('name');

        $this->app['artisan']->add($command);
        $this->artisan('langman:trans', ['key' => 'users.name.first:en']);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $this->assertEquals(['first' => 'name'], $enFile['name']);
    }
}
