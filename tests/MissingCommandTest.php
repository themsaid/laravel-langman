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
        $command->shouldReceive('ask')->once()->with('/user\.age:nl/', null)->andReturn('fill_age');
        $command->shouldReceive('ask')->once()->with('/product\.name:en/', null)->andReturn('fill_name');
        $command->shouldReceive('ask')->once()->with('/product\.color:nl/', null)->andReturn('fill_color');
        $command->shouldReceive('ask')->once()->with('/product\.size:nl/', null)->andReturn('fill_size');
        $command->shouldReceive('ask')->once()->with('/missing\.missing\.id:nl/', null)->andReturn('fill_missing_id');
        $command->shouldReceive('ask')->once()->with('/missing\.missing\.price:en/', null)->andReturn('fill_missing_price');
        $command->shouldReceive('ask')->once()->with('/missing\.missing\.price:nl/', null)->andReturn('fill_missing_price');

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

    public function testAllowSeeTranslationInDefaultLanguage()
    {
        $manager = $this->app[Manager::class];

        $this->app['config']->set('app.locale', 'en');

        $this->createTempFiles([
            'en' => [
                'user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];",
            ],
            'nl' => [
                'user' => "<?php\n return ['name' => 'Naam'];",
            ],
        ]);

        $command = m::mock('\Themsaid\Langman\Commands\MissingCommand[ask]', [$manager]);
        $command->shouldReceive('ask')->once()->with('/<fg=yellow>user\.age:nl<\/> translation/', '/en:Age/');

        $this->app['artisan']->add($command);

        $this->artisan('langman:missing', ['--default' => true]);
    }

    public function testShowsNoDefaultWhenDefaultLanguageFileIsNotFound()
    {
        $manager = $this->app[Manager::class];

        $this->app['config']->set('app.locale', 'es');

        $this->createTempFiles([
            'en' => [
                'user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];",
            ],
            'nl' => [
                'user' => "<?php\n return ['name' => 'Naam'];",
            ],
        ]);

        $command = m::mock('\Themsaid\Langman\Commands\MissingCommand[ask]', [$manager]);
        $command->shouldReceive('ask')->once()->with('/<fg=yellow>user\.age:nl<\/> translation/', null);

        $this->app['artisan']->add($command);

        $this->artisan('langman:missing', ['--default' => true]);
    }
}
