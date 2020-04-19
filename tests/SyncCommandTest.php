<?php

class SyncCommandTest extends TestCase
{
    public function testCommandOutputForFile()
    {
        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));

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
        mkdir(__DIR__.'/views_temp/user');
        file_put_contents(__DIR__.'/views_temp/user/index.blade.php', "{{ trans('user.city') }} {{ trans('user.code.initial') }}");

        $this->createTempFiles([
            'en' => [
                'user' => "<?php\n return ['name' => 'Name', 'not_in_files' => 'a'];",
                '-json' => ['whatever'=>'Sailor', 'json_en'=>'JSON' ]
            ],
            'nl' => [
                'user' => "<?php\n return ['only_in_nl'=>'ja'];",
                '-json' => ['whatever'=>'Matroos', 'json_nl'=>'my_json' ]
            ],
        ]);

        $this->artisan('langman:sync');

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $userNlFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';
        $userJSONFileEN = (array) json_decode(file_get_contents($this->app['config']['langman.path'].'/en.json'), true);
        $userJSONFileNL = (array) json_decode(file_get_contents($this->app['config']['langman.path'].'/nl.json'), true);

        $expectedEN=[
            'name' => 'Name',
            'not_in_files' => 'a',
            'code' => ['initial' => ''],
            'age' => '',
            'city' => '',
            'choice1' => '',
            'choice2' => '',
            'choice3' => '',
            'choice4' => '',
            'ws' => '',
            'only_in_nl' => ''
        ];
        $expectedENJson = [
            'JSON string check' => '',
            'random json string' => '',
            'whatever' => 'Sailor',
            'do something' => '',
            'json_nl' => '',
            'json_en' => 'JSON'
        ];

        $expectedNL=[
            'name' => '',
            'not_in_files' => '',
            'code' => ['initial' => ''],
            'age' => '',
            'city' => '',
            'choice1' => '',
            'choice2' => '',
            'choice3' => '',
            'choice4' => '',
            'ws' => '',
            'only_in_nl'=> 'ja'
        ];
        $expectedNLJson = [
            'JSON string check' => '',
            'random json string' => '',
            'whatever' => 'Matroos',
            'do something' => '',
            'json_nl' => 'my_json',
            'json_en' => ''
        ];

        $this->assertEquals($expectedEN, $userENFile);
        $this->assertEquals($expectedENJson, $userJSONFileEN);
        $this->assertEquals($expectedNL, $userNlFile);
        $this->assertEquals($expectedNLJson, $userJSONFileNL);

        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));
    }

    public function testCommandOutputForMissingSubKey()
    {
        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));

        file_put_contents(__DIR__.'/views_temp/user.blade.php', '{{ trans(\'user.name.first\') }}');
        mkdir(__DIR__.'/views_temp/user');
        file_put_contents(__DIR__.'/views_temp/user/index.blade.php', "{{ trans('user.name.last') }}");

        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => ['middle' => 'middle', 'first' => 'old_value','not_in_files' => 'a']];"],
            'nl' => ['user' => "<?php\n return ['name' => ['middle' => 'middle2', 'first' => 'old_value2']];"],
        ]);

        $this->artisan('langman:sync');

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $userNLFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';

        $expectEN = [
            'name' => ['middle' => 'middle', 'first' => 'old_value', 'last' => '', 'not_in_files' => 'a' ]
        ];
        $expectNL = [
            'name' => ['middle' => 'middle2', 'first' => 'old_value2', 'last'=> '', 'not_in_files' => '' ]
        ];
        $this->assertEquals($expectEN, $userENFile);
        $this->assertEquals($expectNL, $userNLFile);

        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));
    }

    public function testItDoesntOverrideParentKey()
    {
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));

        file_put_contents(__DIR__.'/views_temp/user.blade.php', '{{ trans(\'user.name\') }}');

        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => ['middle' => 'middle']];"],
        ]);

        $this->artisan('langman:sync');

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';

        $this->assertEquals(['middle' => 'middle'], $userENFile['name']);

        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));
    }
}
