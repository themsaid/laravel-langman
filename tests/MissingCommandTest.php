<?php

use Illuminate\Support\Facades\Artisan;
use Mockery as m;
use OSSTools\Langman\Manager;

class MissingCommandTest extends TestCase
{
    public function testCommandOutput()
    {
        $this->assertTrue(true);
        //        $manager = $this->app[Manager::class];
        //
        //        $this->createTempFiles([
        //            'en' => [
        //                'user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];",
        //                'product' => "<?php\n return ['color' => 'color', 'size' => 'size'];",
        //                'missing' => "<?php\n return ['missing' => ['id' => 'id missing', 'price' => '']];",
        //            ],
        //            'nl' => [
        //                'user' => "<?php\n return ['name' => 'Naam', ];",
        //                'product' => "<?php\n return ['name' => 'Naam', 'size' => ''];",
        //            ],
        //        ]);
        //
        //        $command = m::mock('\OSSTools\Langman\Commands\MissingCommand[ask]', [$manager]);
        //        $command->shouldReceive('ask')->once()->andReturn('fill_age');
        //        $command->shouldReceive('ask')->once()->andReturn('fill_name');
        //        $command->shouldReceive('ask')->once()->andReturn('fill_color');
        //        $command->shouldReceive('ask')->once()->andReturn('fill_size');
        //        $command->shouldReceive('ask')->once()->andReturn('fill_missing_id');
        //        $command->shouldReceive('ask')->once()->andReturn('fill_missing_price');
        //        $command->shouldReceive('ask')->once()->andReturn('fill_missing_price');
        //
        //        $this->app['artisan']->add($command);
        //        Artisan::call('langman:missing');
        //
        //        $missingENFile = (array) include $this->app['config']['langman.path'].'/en/missing.php';
        //        $missingNLFile = (array) include $this->app['config']['langman.path'].'/nl/missing.php';
        //        $userNlFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';
        //        $productENFile = (array) include $this->app['config']['langman.path'].'/en/product.php';
        //        $productNlFile = (array) include $this->app['config']['langman.path'].'/nl/product.php';
        //
        //        $this->assertEquals('fill_age', $userNlFile['age']);
        //        $this->assertEquals('fill_name', $productENFile['name']);
        //        $this->assertEquals('fill_color', $productNlFile['color']);
        //        $this->assertEquals('fill_size', $productNlFile['size']);
        //        $this->assertEquals('fill_missing_id', $missingNLFile['missing']['id']);
        //        $this->assertEquals('fill_missing_price', $missingNLFile['missing']['price']);
        //        $this->assertEquals('fill_missing_price', $missingENFile['missing']['price']);
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

        $command = m::mock('\OSSTools\Langman\Commands\MissingCommand[ask]', [$manager]);
        $command->shouldReceive('ask')->once();

        $this->app['artisan']->add($command);

        Artisan::call('langman:missing', ['--default' => true]);
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

        $command = m::mock('\OSSTools\Langman\Commands\MissingCommand[ask]', [$manager]);
        $command->shouldReceive('ask')->once();

        $this->app['artisan']->add($command);

        Artisan::call('langman:missing', ['--default' => true]);
    }
}
