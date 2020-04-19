<?php

use Illuminate\Filesystem\Filesystem;
use Mockery as m;

class ManagerTest extends TestCase
{
    public function testFilesMethod()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['user' => '', 'category' => '', "-json"=>[]],
            'nl' => ['user' => '', 'category' => '', "-json"=>[]],
            'vendor' => ['package' => ['en' => ['user' => '', 'product' => ''], 'sp' => ['user' => '', 'product' => '']]],
        ]);

        $expected = [
            'user' => [
                'en' => __DIR__.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'en'.DIRECTORY_SEPARATOR.'user.php',
                'nl' => __DIR__.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'nl'.DIRECTORY_SEPARATOR.'user.php',
            ],
            'category' => [
                'en' => __DIR__.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'en'.DIRECTORY_SEPARATOR.'category.php',
                'nl' => __DIR__.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'nl'.DIRECTORY_SEPARATOR.'category.php',
            ],
            "-json" => [
                'en' => __DIR__.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'en.json',
                'nl' => __DIR__.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'nl.json',
            ]
// Uncomment when starting to support vendor language files
//            'package::product' => [
//                'en' => __DIR__.'/temp/vendor/package/en/product.php',
//                'sp' => __DIR__.'/temp/vendor/package/sp/product.php',
//            ],
//            'package::user' => [
//                'en' => __DIR__.'/temp/vendor/package/en/user.php',
//                'sp' => __DIR__.'/temp/vendor/package/sp/user.php',
//            ],
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
            'en' => ["-json"=>[]],
            'sp' => [],
            'nl' => ['user' => '__UN_TOUCHED__'],
        ]);

        $manager->createFile('user');

        $this->assertFileExists($this->app['config']['langman.path'].'/en/user.php');
        $this->assertFileExists($this->app['config']['langman.path'].'/sp/user.php');
        $this->assertFileNotExists($this->app['config']['langman.path'].'/sp.json');
        $this->assertFileNotExists($this->app['config']['langman.path'].'/nl.json');
        $this->assertEquals('__UN_TOUCHED__', file_get_contents($this->app['config']['langman.path'].'/nl/user.php'));
        $this->assertEquals([], (array) include $this->app['config']['langman.path'].'/en/user.php');
        $this->assertEquals([], (array) include $this->app['config']['langman.path'].'/sp/user.php');

        $manager->createFile('-json', "sp");

        $this->assertFileExists($this->app['config']['langman.path'].'/en.json');
        $this->assertFileExists($this->app['config']['langman.path'].'/sp.json');
        $this->assertFileNotExists($this->app['config']['langman.path'].'/nl.json');
        $this->assertEquals([], (array) json_decode(file_get_contents($this->app['config']['langman.path'].'/en.json'), true));
        $this->assertEquals([], (array) json_decode(file_get_contents($this->app['config']['langman.path'].'/sp.json'), true));
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
            'double_quotes' => '"with quotes"',
            'quotes' => "With some ' quotes",
        ];

        $manager->writeFile($filePath, $values);

        $this->assertEquals($values, (array) include $filePath);
    }

    public function testWriteJSONFile()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ["-json" => []],
            'nl' => ["-json" => []],
        ]);

        $filePath = $this->app['config']['langman.path'].'/en.json';

        $values = [
            'name' => ['first' => 'first', 'last' => ['last1' => '1', 'last2' => 2]],
            'age' => 'age',
            'double_quotes' => '"with quotes"',
            'quotes' => "With some ' quotes",
        ];

        $manager->writeFile($filePath, $values);

        $this->assertEquals($values, $manager->getFileContent($filePath));
    }

    public function testGetFileContentReadsContent()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $values = ["Test1"=>"Value1", 'Test2'=>'Value2'];
        $this->createTempFiles([
            'en' => ['users' => "<?php return ['_content_'];", "-json"=>$values],
        ]);

        $filePath = $this->app['config']['langman.path'].'/en/users.php';

        $this->assertContains('_content_', $manager->getFileContent($filePath));
        $this->assertEquals($values, $manager->getFileContent($this->app['config']['langman.path'].'/en.json'));
    }

    public function testGetFileContentThrowsExceptionIfNotFound()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles();

        $filePath = $this->app['config']['langman.path'].'/en/users.php';

        $this->expectException('\Illuminate\Contracts\Filesystem\FileNotFoundException');
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

    public function testRemoveTranslationLineFromJSONFiles()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['-json' => ['String 1'=>'Trans 1', 'String 2'=>'Trans 2']],
            'nl' => ['-json' => ['String 1'=>'Hola 1', 'String 2'=>'Hola 2']],
        ]);

        $manager->removeKey('-json', 'String 1');

        $enFile = $manager->getFileContent($this->app['config']['langman.path'].'/en.json');
        $nlFile = $manager->getFileContent($this->app['config']['langman.path'].'/nl.json');

        $this->assertArrayNotHasKey('String 1', $enFile);
        $this->assertArrayHasKey('String 2', $enFile);
        $this->assertArrayNotHasKey('String 1', $nlFile);
        $this->assertArrayHasKey('String 2', $nlFile);
    }

    public function testRemoveNestedTranslationLineFromAllFiles()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => "<?php return ['name'=> ['f' => '1', 's' => 2], 'age' => 'b'];"],
            'nl' => ['users' => "<?php return ['name'=> ['f' => 'nl1', 's'=> 'nl2'], 'age' => 'd'];"],
        ]);

        $manager->removeKey('users', 'name.f');

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $nlFile = (array) include $this->app['config']['langman.path'].'/nl/users.php';

        $this->assertArrayHasKey('name', $enFile);
        $this->assertArrayNotHasKey('f', $enFile['name']);
        $this->assertArrayHasKey('age', $enFile);
        $this->assertArrayHasKey('name', $nlFile);
        $this->assertArrayNotHasKey('f', $nlFile['name']);
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

    public function testFillJSONTranslationLinesThatDoesNotExistYet()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['-json' => []],
            'nl' => ['-json' => []],
        ]);

        $manager->fillKeys('-json', ['name' => ['en' => 'name', 'nl' => 'naam']]);

        $enFile = $manager->getFileContent($this->app['config']['langman.path'].'/en.json');
        $nlFile = $manager->getFileContent($this->app['config']['langman.path'].'/nl.json');

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

    public function testUpdatesJSONTranslationLineThatExists()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $enval = ['String 1'=>'Test 1', 'String 2'=>'Test 2'];
        $nlval = ['String 1'=>'Hola 1', 'String 2'=>'Hola 2'];
        $this->createTempFiles([
            'en' => ['-json' => $enval],
            'nl' => ['-json' => $nlval],
        ]);

        $manager->fillKeys('-json', ['String 1' => ['en' => 'name']]);

        $enFile = $manager->getFileContent($this->app['config']['langman.path'].'/en.json');
        $nlFile = $manager->getFileContent($this->app['config']['langman.path'].'/nl.json');

        $enval['String 1']='name';
        $this->assertEquals($enval, $enFile);
        $this->assertEquals($nlval, $nlFile);
    }

    public function testFillNestedTranslationLines()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => '<?php return ["class" => "class"];'],
            'nl' => ['users' => '<?php return ["name" => ["first" => "nil"]];'],
        ]);

        $manager->fillKeys('users', ['name.first' => ['en' => 'name', 'nl' => 'naam']]);

        $enFile = (array) include $this->app['config']['langman.path'].'/en/users.php';
        $nlFile = (array) include $this->app['config']['langman.path'].'/nl/users.php';

        $this->assertEquals('name', $enFile['name']['first']);
        $this->assertEquals('class', $enFile['class']);
        $this->assertEquals('naam', $nlFile['name']['first']);
    }

    public function testFindTranslationsInProjectFiles()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        array_map('unlink', glob(__DIR__.'/views_temp/users/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/users'));
        array_map('unlink', glob(__DIR__.'/views_temp/users.blade.php'));

        mkdir(__DIR__.'/views_temp/users');
        file_put_contents(__DIR__.'/views_temp/users/index.blade.php', "{{ trans('users.city') }}");
        file_put_contents(__DIR__.'/views_temp/user.blade.php', <<<HEREDOC
// Translations cannot start at offset 0 in the file, the regex fails on that
trans('user.name')
trans('user.age')
__('JSON string check')
 trans_choice('user.choice1',12)
 Lang::get('random json string')
 Lang::choice('user.choice2','param')
 Lang::trans('whatever')
 Lang::transChoice('user.choice3','another')
 @lang('do something')
 @choice('user.choice4','old')

Allow whitespace:
trans     (     'user.ws'   
   )

Skip these
->trans('not1')
___('not2')
@ lang('not3')
@ choice('not4')
trans(\$var)
trans()
anytrans('user.not5')
trans ( 'user.not6' . \$additional )

HEREDOC
);

        $results = $manager->collectFromFiles();

        array_map('unlink', glob(__DIR__.'/views_temp/users/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/users'));
        array_map('unlink', glob(__DIR__.'/views_temp/users.blade.php'));

        $expected = [
            "-json" => [
                "JSON string check",
                "random json string",
                "whatever",
                "do something",
            ],
            "user" => [
                "name",
                "age",
                "choice1",
                "choice2",
                "choice3",
                "choice4",
                "ws"
            ],
            "users" => [
                "city"
            ]
        ];

        $this->assertEquals($expected, $results);
    }

    public function testGetKeysExistingInALanguageButNotTheOther()
    {
        $manager = m::mock('Themsaid\Langman\Manager[languages]', [new Filesystem(), '', []]);

        $manager->shouldReceive('languages')->andReturn(['en', 'nl']);

        $results = $manager->getKeysExistingInALanguageButNotTheOther([
            'user.en.name' => 'a',
            'user.nl.phone' => 'a',
            'user.en.address' => 'a',
            'user.nl.address' => 'a',
            "-json.en.city" => 'city',
            "-json.nl.handy" => 'phone'
        ]);

        $this->assertContains('user.name:nl', $results);
        $this->assertContains('user.phone:en', $results);
        $this->assertContains('-json.city:nl', $results);
        $this->assertContains('-json.handy:en', $results);
        $this->assertNotContains('user.address:en', $results);
        $this->assertNotContains('user.address:nl', $results);
        $this->assertNotContains('-json.city:en', $results);
        $this->assertNotContains('-json.handy:nl', $results);
    }
}
