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
        
        array_map('unlink', glob(__DIR__.'/app_temp/Http/Controllers/testController.php'));
        array_map('unlink', glob(__DIR__.'/app_temp/Jobs/testJob.php'));
        array_map('rmdir', glob(__DIR__.'/app_temp/Http/Controllers'));
        array_map('rmdir', glob(__DIR__.'/app_temp/Http'));
        array_map('rmdir', glob(__DIR__.'/app_temp/Jobs'));
        mkdir(__DIR__.'/app_temp/Http');
        mkdir(__DIR__.'/app_temp/Http/Controllers');
        mkdir(__DIR__.'/app_temp/Jobs');


        file_put_contents(__DIR__.'/app_temp/Http/Controllers/testController.php', "<?php class testController{ public function index() { return Lang::get('user.first_name'); } }");

        file_put_contents(__DIR__.'/app_temp/Jobs/testJob.php', "<?php class testJob{ public function index() { return Lang::get('user.last_name'); } }");

        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name'];"],
            'nl' => ['user' => "<?php\n return [];"],
        ]);

        $this->artisan('langman:sync');

        $userENFile = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $userNlFile = (array) include $this->app['config']['langman.path'].'/nl/user.php';

        $this->assertArrayHasKey('name', $userENFile);
        $this->assertArrayHasKey('initial', $userENFile['code']);
        $this->assertArrayHasKey('age', $userENFile);
        $this->assertArrayHasKey('city', $userENFile);
        $this->assertArrayHasKey('first_name', $userENFile);
        $this->assertArrayHasKey('last_name', $userENFile);
        $this->assertArrayHasKey('name', $userNlFile);
        $this->assertArrayHasKey('initial', $userNlFile['code']);
        $this->assertArrayHasKey('age', $userNlFile);
        $this->assertArrayHasKey('city', $userNlFile);
        $this->assertArrayHasKey('first_name', $userNlFile);
        $this->assertArrayHasKey('last_name', $userNlFile);
        
        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));

        array_map('unlink', glob(__DIR__.'/app_temp/Http/Controllers/testController.php'));
        array_map('unlink', glob(__DIR__.'/app_temp/Jobs/testJob.php'));
        array_map('rmdir', glob(__DIR__.'/app_temp/Http/Controllers'));
        array_map('rmdir', glob(__DIR__.'/app_temp/Http'));
        array_map('rmdir', glob(__DIR__.'/app_temp/Jobs'));
    }
}
