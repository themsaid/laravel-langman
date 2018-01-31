<?php

class UnusedCommandTest extends TestCase
{
    public function testCommandOutputForFile()
    {
        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));

        file_put_contents(__DIR__.'/views_temp/user.blade.php', "{{ trans('user.name') }} {{ trans('user.age') }}");
        mkdir(__DIR__.'/views_temp/user');
        file_put_contents(__DIR__.'/views_temp/user/index.blade.php', "{{ trans('user.city') }} {{ trans('user.code.initial') }}");

        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'not_in_files_en' => 'a'];"],
            'nl' => ['user' => "<?php\n return ['city' => 'city', 'nop' => ['not_in_files_nl' => 'b']];"],
        ]);

        $this->artisan('langman:unused');
        
        $this->assertContains('en.not_in_files_en', $this->consoleOutput());
        $this->assertContains('nl.nop.not_in_files_nl', $this->consoleOutput());
        

        array_map('unlink', glob(__DIR__.'/views_temp/user/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/user'));
        array_map('unlink', glob(__DIR__.'/views_temp/user.blade.php'));
    }
}
