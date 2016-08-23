<?php

class SyncCommandTest extends TestCase
{
    public function testCommandOutputForFile()
    {
        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));

        file_put_contents(__DIR__.'/views_temp/user.blade.php', '{{ trans(\'user.name\') }} {{ trans(\'user.age\') }}');
        mkdir(__DIR__.'/views_temp/user');
        file_put_contents(__DIR__.'/views_temp/user/index.blade.php', "{{ trans('user.city') }} {{ trans('user.code.initial') }}");

        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'not_in_files' => 'a'];"],
            'nl' => ['user' => "<?php\n return [];"],
        ]);

        $this->artisan('langman:sync');

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $userNlFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';

        $this->assertArrayHasKey('name', $userENFile);
        $this->assertArrayHasKey('not_in_files', $userENFile);
        $this->assertArrayHasKey('initial', $userENFile['code']);
        $this->assertArrayHasKey('age', $userENFile);
        $this->assertArrayHasKey('city', $userENFile);
        $this->assertArrayHasKey('name', $userNlFile);
        $this->assertArrayHasKey('not_in_files', $userNlFile);
        $this->assertArrayHasKey('initial', $userNlFile['code']);
        $this->assertArrayHasKey('age', $userNlFile);
        $this->assertArrayHasKey('city', $userNlFile);

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
            'nl' => ['user' => "<?php\n return ['name' => ['middle' => 'middle', 'first' => 'old_value']];"],
        ]);

        $this->artisan('langman:sync');

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $userNLFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';

        $this->assertArrayHasKey('not_in_files', $userNLFile['name']);
        $this->assertArrayHasKey('name', $userENFile);
        $this->assertArrayHasKey('first', $userENFile['name']);
        $this->assertEquals('old_value', $userENFile['name']['first']);
        $this->assertArrayHasKey('last', $userENFile['name']);
        $this->assertArrayHasKey('middle', $userENFile['name']);

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
