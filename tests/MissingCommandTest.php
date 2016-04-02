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
            ],
            'nl' => [
                'user' => "<?php\n return ['name' => 'Naam', ];",
                'product' => "<?php\n return ['name' => 'Naam', 'size' => ''];",
            ],
        ]);

        $command = m::mock('\Themsaid\Langman\Commands\MissingCommand[ask]', [$manager]);
        $command->shouldReceive('ask')->with('user.age.nl translation: (Hint: en = "Age")')->andReturn('fill_age');
        $command->shouldReceive('ask')->with('product.name.en translation: (Hint: nl = "Naam")')->andReturn('fill_name');
        $command->shouldReceive('ask')->with('product.color.nl translation: (Hint: en = "color")')->andReturn('fill_color');
        $command->shouldReceive('ask')->with('product.size.nl translation:')->andReturn('fill_size');

        $this->app['artisan']->add($command);
        $this->artisan('langman:missing');

        $userNlFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';
        $productENFile = (array) include $this->app['config']['langman.path'].'/en/product.php';
        $productNlFile = (array) include $this->app['config']['langman.path'].'/nl/product.php';

        $this->assertEquals('fill_age', $userNlFile['age']);
        $this->assertEquals('fill_name', $productENFile['name']);
        $this->assertEquals('fill_color', $productNlFile['color']);
        $this->assertEquals('fill_size', $productNlFile['size']);
    }
}
