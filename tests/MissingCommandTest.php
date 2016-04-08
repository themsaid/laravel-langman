<?php

use Themsaid\Langman\Manager;
use Mockery as m;

class MissingCommandTest extends TestCase
{
    public function testCommandOutput()
    {
        $manager = $this->app[Manager::class];

        $this->createTempFiles([
            'en' => [
                'user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];",
                'product' => "<?php\n return ['color' => 'color', 'size' => 'size'];",
                'missing' => "<?php\n return ['missing' => ['id' => 'id missing', 'price' => '']];",
            ],
            'nl' => [
                'user' => "<?php\n return ['name' => 'Naam', ];",
                'product' => "<?php\n return ['name' => 'Naam', 'size' => ''];",
            ],
        ]);

        $command = m::mock('\Themsaid\Langman\Commands\MissingCommand[ask]', [$manager]);
        $command->shouldReceive('ask')->once()->with('user.age:nl translation')->andReturn('fill_age');
        $command->shouldReceive('ask')->once()->with('product.name:en translation')->andReturn('fill_name');
        $command->shouldReceive('ask')->once()->with('product.color:nl translation')->andReturn('fill_color');
        $command->shouldReceive('ask')->once()->with('product.size:nl translation')->andReturn('fill_size');
        $command->shouldReceive('ask')->once()->with('missing.missing.id:nl translation')->andReturn('fill_missing_id');
        $command->shouldReceive('ask')->once()->with('missing.missing.price:en translation')->andReturn('fill_missing_price');
        $command->shouldReceive('ask')->once()->with('missing.missing.price:nl translation')->andReturn('fill_missing_price');

        $this->app['artisan']->add($command);
        $this->artisan('langman:missing');

        $missingENFile = (array) include $this->app['config']['langman.path'].'/en/missing.php';
        $missingNLFile = (array) include $this->app['config']['langman.path'].'/nl/missing.php';
        $userNlFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';
        $productENFile = (array) include $this->app['config']['langman.path'].'/en/product.php';
        $productNlFile = (array) include $this->app['config']['langman.path'].'/nl/product.php';

        $this->assertEquals('fill_age', $userNlFile['age']);
        $this->assertEquals('fill_name', $productENFile['name']);
        $this->assertEquals('fill_color', $productNlFile['color']);
        $this->assertEquals('fill_size', $productNlFile['size']);
        $this->assertEquals('fill_missing_id', $missingNLFile['missing']['id']);
        $this->assertEquals('fill_missing_price', $missingNLFile['missing']['price']);
        $this->assertEquals('fill_missing_price', $missingENFile['missing']['price']);
    }
}
