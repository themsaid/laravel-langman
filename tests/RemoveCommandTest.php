<?php

use Themsaid\Langman\Manager;
use Mockery as m;

class RemoveCommandTest extends TestCase
{
    public function testCommandOutput()
    {
        $manager = $this->app[Manager::class];

        $this->createTempFiles([
            'en' => [
                'user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];",
            ],
            'nl' => [
                'user' => "<?php\n return ['name' => 'Naam'];",
            ],
        ]);

        $command = m::mock('\Themsaid\Langman\Commands\RemoveCommand[confirm]', [$manager]);
        $command->shouldReceive('confirm')->once()->with('Are you sure you want to remove "user.name"?')->andReturn(true);

        $this->app['artisan']->add($command);
        $this->artisan('langman:remove', ['key' => 'user.name']);

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $userNLFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';

        $this->assertArrayNotHasKey('name', $userENFile);
        $this->assertArrayNotHasKey('name', $userNLFile);
    }

    public function testRemovesNestedKeys()
    {
        $manager = $this->app[Manager::class];

        $this->createTempFiles([
            'en' => [
                'user' => "<?php\n return ['name' => ['f'=>1,'l'=>2], 'age' => 'Age'];",
            ],
            'nl' => [
                'user' => "<?php\n return ['name' => ['f'=>1,'l'=>2]];",
            ],
        ]);

        $command = m::mock('\Themsaid\Langman\Commands\RemoveCommand[confirm]', [$manager]);
        $command->shouldReceive('confirm')->once()->andReturn(true);

        $this->app['artisan']->add($command);
        $this->artisan('langman:remove', ['key' => 'user.name.f']);

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $userNLFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';

        $this->assertArrayHasKey('name', $userENFile);
        $this->assertArrayHasKey('name', $userNLFile);
        $this->assertArrayNotHasKey('f', $userENFile['name']);
        $this->assertArrayNotHasKey('f', $userNLFile['name']);
    }

    public function testRemovesParentOfNestedKeys()
    {
        $manager = $this->app[Manager::class];

        $this->createTempFiles([
            'en' => [
                'user' => "<?php\n return ['name' => ['f'=>1,'l'=>2], 'age' => 'Age'];",
            ],
            'nl' => [
                'user' => "<?php\n return ['name' => ['f'=>1,'l'=>2]];",
            ],
        ]);

        $command = m::mock('\Themsaid\Langman\Commands\RemoveCommand[confirm]', [$manager]);
        $command->shouldReceive('confirm')->once()->andReturn(true);

        $this->app['artisan']->add($command);
        $this->artisan('langman:remove', ['key' => 'user.name']);

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $userNLFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';

        $this->assertArrayNotHasKey('name', $userENFile);
        $this->assertArrayNotHasKey('name', $userNLFile);
    }

    public function testCommandOutputForVendorPackage()
    {
        $manager = $this->app[Manager::class];

        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['weight' => 'weight'];", 'category' => ''],
            'nl' => ['user' => '', 'category' => ''],
            'vendor' => ['package' => ['en' => ['file' => "<?php\n return ['not_found' => 'file not found here'];"], 'sp' => ['file' => "<?php\n return ['not_found' => 'something'];"]]],
        ]);

        $command = m::mock('\Themsaid\Langman\Commands\RemoveCommand[confirm]', [$manager]);
        $command->shouldReceive('confirm')->once()->with('Are you sure you want to remove "package::file.not_found"?')->andReturn(true);

        $this->app['artisan']->add($command);
        $this->artisan('langman:remove', ['key' => 'package::file.not_found']);

        $ENFile = (array) include $this->app['config']['langman.path'].'/vendor/package/en/file.php';
        $SPFile = (array) include $this->app['config']['langman.path'].'/vendor/package/sp/file.php';

        $this->assertArrayNotHasKey('name', $ENFile);
        $this->assertArrayNotHasKey('name', $SPFile);
    }
}
