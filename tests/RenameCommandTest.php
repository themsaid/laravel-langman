<?php

use Themsaid\Langman\Manager;

class RenameCommandTest extends TestCase
{
    public function testRenameAKeyValue ()
    {
        $this->createTempFiles ([
            'en' => ['user' => "<?php\n return['mobile' => 'Mobile'];"],
        ]);
        $expectedValues = ['contact' => 'Mobile'];

        $this->artisan ( 'langman:rename', ['key' => 'user.mobile', 'as' => 'contact'] );

        $newValue = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $this->assertEquals ( $expectedValues, $newValue );
    }

    public function testRenameAKeyValueForAllLanguages (  )
    {
        $this->createTempFiles ([
            'en' => ['user' => "<?php\n return['mobile' => 'Mobile'];"],
            'es' => ['user' => "<?php\n return['mobile' => 'Movil'];"],
        ]);
        $expectedValueEN = ['contact' => 'Mobile'];
        $expectedValueES = ['contact' => 'Movil'];

        $this->artisan ( 'langman:rename', ['key' => 'user.mobile', 'as' => 'contact'] );

        $newValueEN = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $newValueES = (array) include $this->app['config']['langman.path'].'/es/user.php';
        $this->assertEquals ( $expectedValueEN, $newValueEN );
        $this->assertEquals ( $expectedValueES, $newValueES );
    }

    public function testRenameANestedKeyValueForAllLanguages (  )
    {
        $this->createTempFiles ([
            'en' => ['user' => "<?php\n return['contact' => ['cellphone' => 'Mobile']];"],
            'es' => ['user' => "<?php\n return['contact' => ['cellphone' => 'Movil']];"],
        ]);
        $expectedValueEN = ['contact' => ['mobile' => 'Mobile']];
        $expectedValueES = ['contact' => ['mobile' => 'Movil']];

        $this->artisan ( 'langman:rename', ['key' => 'user.contact.cellphone', 'as' => 'mobile'] );

        $newValueEN = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $newValueES = (array) include $this->app['config']['langman.path'].'/es/user.php';
        $this->assertEquals ( $expectedValueEN, $newValueEN );
        $this->assertEquals ( $expectedValueES, $newValueES );
    }

    public function testRenameOfANestedKeyValueForAllLanguagesInAnyDepth (  )
    {
        $this->createTempFiles ([
            'en' => ['user' => "<?php\n return['contact' => ['mobile' => 'Mobile', 'others' => ['msn' => 'E-mail']]];"],
            'es' => ['user' => "<?php\n return['contact' => ['mobile' => 'Movil', 'others' => ['msn' => 'Correo electronico']]];"],
        ]);

        $expectedValueEN = ['contact' => ['mobile' => 'Mobile', 'others' => ['mail' => 'E-mail']]];
        $expectedValueES = ['contact' => ['mobile' => 'Movil', 'others' => ['mail' => 'Correo electronico']]];

        $this->artisan ( 'langman:rename', ['key' => 'user.contact.others.msn', 'as' => 'mail'] );

        $newValueEN = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $newValueES = (array) include $this->app['config']['langman.path'].'/es/user.php';
        $this->assertEquals ( $expectedValueEN, $newValueEN );
        $this->assertEquals ( $expectedValueES, $newValueES );
    }

    public function testRenameCommandShowViewFilesAffectedForTheChange( )
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles ([
            'en' => ['users' => "<?php\n return['name' => 'Name'];"],
        ]);

        array_map('unlink', glob(__DIR__.'/views_temp/users/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/users'));
        array_map('unlink', glob(__DIR__.'/views_temp/users.blade.php'));

        file_put_contents(__DIR__.'/views_temp/users.blade.php', "{{ trans('users.name') }} {{ trans('users.age') }}");
        mkdir(__DIR__.'/views_temp/users');
        file_put_contents(__DIR__.'/views_temp/users/index.blade.php', "{{ trans('users.name') }} {{ trans('users.city') }} {{ trans('users.name') }}");

        $this->artisan ( 'langman:rename', ['key' => 'users.name', 'as' => 'username'] );

        array_map('unlink', glob(__DIR__.'/views_temp/users/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/users'));
        array_map('unlink', glob(__DIR__.'/views_temp/users.blade.php'));

        $this->assertContains("2 views files has been affected.\n", $this->consoleOutput());
        $this->assertRegExp('/Times(?:.*)View File/', $this->consoleOutput());
        $this->assertRegExp('/1(?:.*)users\.blade\.php/', $this->consoleOutput());
        $this->assertRegExp('/2(?:.*)users\\\index\.blade\.php/', $this->consoleOutput());
    }

    public function testThrowErrorMessageForInvalidKeyArgument (  )
    {
        $this->artisan ( 'langman:rename', ['key' => 'name', 'as' => 'username'] );
        
        $this->assertContains ( 'Invalid <key> argument format! Pls check and try again.', $this->consoleOutput () );
    }

    public function testThrowErrorMessageForInvalidAsArgument (  )
    {
        $this->artisan ( 'langman:rename', ['key' => 'user.name', 'as' => 'user.username'] );

        $this->assertContains ( 'Invalid <as> argument format! Pls check and try again.', $this->consoleOutput () );
    }
    
    
}
