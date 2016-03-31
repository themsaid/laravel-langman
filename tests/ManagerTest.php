<?php

use Mockery as m;

class TestManager extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('langman.path', __DIR__.'/temp');
    }

    public function testFilesMethod()
    {
        $manager = $this->app[\Themsaid\LangMan\Manager::class];

        $this->createTempFiles([
            'en' => ['user' => '', 'category' => ''],
            'nl' => ['user' => '', 'category' => ''],
        ]);

        $expected = [
            'user' => [
                'en' => __DIR__.'/temp/en/user.php',
                'nl' => __DIR__.'/temp/nl/user.php',
            ],
            'category' => [
                'en' => __DIR__.'/temp/en/category.php',
                'nl' => __DIR__.'/temp/nl/category.php',
            ]
        ];

        $this->assertEquals($expected, $manager->files());
    }
}