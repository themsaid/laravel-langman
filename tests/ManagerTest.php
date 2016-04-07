<?php


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
            ],
        ];

        $this->assertEquals($expected, $manager->files());
    }

    public function testLanguagesMethod()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => [],
            'nl' => [],
            'sp' => [],
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
        $this->assertEquals([], (array) include $this->app['config']['langman.path'].'/en/user.php');
    }

    public function testWriteFile()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => ''],
            'nl' => ['users' => ''],
        ]);

        $filePath = $this->app['config']['langman.path'].'/en/user.php';

        $values = [
            'name' => ['first' => 'first', 'last' => ['last1' => '1', 'last2' => 2]],
            'age' => 'age',
        ];

        $manager->writeFile($filePath, $values);

        $this->assertEquals($values, (array) include $filePath);
    }

    public function testGetFileContentReadsContent()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => "<?php return ['_content_'];"],
        ]);

        $filePath = $this->app['config']['langman.path'].'/en/users.php';

        $this->assertContains('_content_', $manager->getFileContent($filePath));
    }

    /**
     * @expectedException Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetFileContentThrowsExceptionIfNotFound()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles();

        $filePath = $this->app['config']['langman.path'].'/en/users.php';

        $manager->getFileContent($filePath);
    }

    public function testGetFileContentCreatesFileIfNeeded()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles();

        $filePath = $this->app['config']['langman.path'].'/en/users.php';

        $manager->getFileContent($filePath, true);

        $this->assertEquals([], (array) include $filePath);
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

    public function testFillTranslationLinesThatDoesNotExistYet()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => '<?php return [];'],
            'nl' => ['users' => '<?php return [];'],
        ]);

        $manager->fillKeys('users', ['name' => ['en' => 'name', 'nl' => 'naam']]);

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

        $manager->fillKeys('users', ['name' => ['en' => 'name']]);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';

        $this->assertEquals('name', $enFile['name']);
    }

    public function testFindTranslationsInViews()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        array_map('unlink', glob(__DIR__.'/views_temp/users/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/users'));
        array_map('unlink', glob(__DIR__.'/views_temp/users.blade.php'));

        file_put_contents(__DIR__.'/views_temp/users.blade.php', '{{ trans(\'users.name\') }} {{ trans(\'users.age\') }}');
        mkdir(__DIR__.'/views_temp/users');
        file_put_contents(__DIR__.'/views_temp/users/index.blade.php', "{{ trans('users.city') }}");

        $results = $manager->collectFromViews();

        array_map('unlink', glob(__DIR__.'/views_temp/users/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/users'));
        array_map('unlink', glob(__DIR__.'/views_temp/users.blade.php'));

        $this->assertArrayHasKey('users', $results);
        $this->assertContains('name', $results['users']);
        $this->assertContains('age', $results['users']);
        $this->assertContains('city', $results['users']);
    }
}
