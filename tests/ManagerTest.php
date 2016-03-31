<?php

use Mockery as m;

class ManagerTest extends TestCase
{
    public function testFilesMethod()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

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

    public function testLanguagesMethod()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => [],
            'sp' => [],
            'nl' => [],
        ]);

        $this->assertEquals(['en', 'nl', 'sp'], $manager->languages());
    }

    public function testCreateFileIfNotExisting()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => [],
            'sp' => [],
            'nl' => ['user' => '__UN_TOUCHED__'],
        ]);

        $manager->createFile('user');

        $this->assertFileExists($this->app['config']['langman.path'].'/en/user.php');
        $this->assertFileExists($this->app['config']['langman.path'].'/sp/user.php');
        $this->assertEquals('__UN_TOUCHED__', file_get_contents($this->app['config']['langman.path'].'/nl/user.php'));
    }

    public function testWriteFile()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => ""],
            'nl' => ['users' => ""],
        ]);

        $filePath = $this->app['config']['langman.path'].'/en/user.php';

        $values = [
            'name' => 'name',
            'age' => 'age'
        ];

        $manager->writeFile($filePath, $values);

        $this->assertEquals($values, (array) include $filePath);
    }

    public function testRemoveTranslationLineFromAllFiles()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => "<?php return ['name'=> 'a', 'age' => 'b'];"],
            'nl' => ['users' => "<?php return ['name'=> 'c', 'age' => 'd'];"],
        ]);

        $manager->removeKey('users', 'name');

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $nlFile = (array) include $this->app['config']['langman.path'].'/nl/users.php';

        $this->assertArrayNotHasKey('name', $enFile);
        $this->assertArrayHasKey('age', $enFile);
        $this->assertArrayNotHasKey('name', $nlFile);
        $this->assertArrayHasKey('age', $nlFile);
    }

    public function testFillTranslationLineThatDoesNotExistYet()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => "<?php return [];"],
            'nl' => ['users' => "<?php return [];"],
        ]);

        $manager->fillKey('users', 'name', ['en' => 'name', 'nl' => 'naam']);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $nlFile = (array) include $this->app['config']['langman.path'].'/nl/users.php';

        $this->assertEquals('name', $enFile['name']);
        $this->assertEquals('naam', $nlFile['name']);
    }

    public function testUpdatesTranslationLineThatExists()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => "<?php return ['name' => 'nil'];"],
        ]);

        $manager->fillKey('users', 'name', ['en' => 'name']);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';

        $this->assertEquals('name', $enFile['name']);
    }
}