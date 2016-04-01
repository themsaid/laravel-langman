<?php

class SyncCommandTest extends TestCase
{
    public function testCommandOutputForFile()
    {
        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));

        file_put_contents(__DIR__.'/views_temp/user.blade.php', '{{ trans(\'user.name\') }} {{ trans(\'user.age\') }}');;
        mkdir(__DIR__.'/views_temp/user');
        file_put_contents(__DIR__.'/views_temp/user/index.blade.php', "{{ trans('user.city') }}");;

        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name'];"],
            'nl' => ['user' => "<?php\n return [];"],
        ]);

        $this->artisan('langman:sync');

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $userNlFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';

        $this->assertArrayHasKey('name', $userENFile, 'en');
        $this->assertArrayHasKey('age', $userENFile, 'en');
        $this->assertArrayHasKey('city', $userENFile, 'en');
        $this->assertArrayHasKey('name', $userNlFile, 'nl');
        $this->assertArrayHasKey('age', $userNlFile, 'nl');
        $this->assertArrayHasKey('city', $userNlFile, 'nl');

        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));
    }
}
