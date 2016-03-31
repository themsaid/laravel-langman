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
}
