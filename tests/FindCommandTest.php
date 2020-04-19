<?php

class FindCommandTest extends TestCase
{
    public function testCommandErrorOnFilesNotFound()
    {
        array_map('unlink', glob(__DIR__.'/temp/*/*'));
        array_map('rmdir', glob(__DIR__.'/temp/*'));

        $this->createTempFiles();

        $this->artisan('langman:find', ['keyword' => 'ragnar']);
        $this->assertStringContainsString('No language files were found!', $this->consoleOutput());
    }

    public function testCommandOutputForFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['not_found' => 'User NoT fOunD', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['not_found' => 'something'];"],
            'sp' => ['user' => "<?php\n return ['else' => 'else'];"],
        ]);

        $this->artisan('langman:find', ['keyword' => 'not found']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/user\.not_found(?:.*)User NoT fOunD(?:.*)something/', $this->consoleOutput());
        $this->assertStringNotContainsString('age', $this->consoleOutput());
        $this->assertStringNotContainsString('else', $this->consoleOutput());
    }

    public function testCommandOutputForFileWithNestedKeys()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['missing' => ['not_found' => 'user not found'], 'jarl_borg' => 'flying'];"],
            'sp' => ['user' => "<?php\n return ['missing' => ['not_found' => 'sp']];"],
        ]);

        $this->artisan('langman:find', ['keyword' => 'not found']);

        $this->assertRegExp('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertRegExp('/user\.missing\.not_found(?:.*)user not found(?:.*)sp/', $this->consoleOutput());
        $this->assertStringNotContainsString('jarl_borg', $this->consoleOutput());
    }

    public function testCommandOutputForPackage()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['weight' => 'weight'];", 'category' => ''],
            'nl' => ['user' => '', 'category' => ''],
            'vendor' => ['package' => ['en' => ['file' => "<?php\n return ['not_found' => 'file not found here'];"], 'sp' => ['file' => "<?php\n return ['not_found' => 'something'];"]]],
        ]);

        $this->artisan('langman:find', ['keyword' => 'not found', '--package' => 'package']);

        $this->assertRegExp('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertRegExp('/package::file\.not_found(?:.*)file not found here(?:.*)something/', $this->consoleOutput());
        $this->assertStringNotContainsString('weight', $this->consoleOutput());
    }

    public function testCommandOutputForJson()
    {
        $this->createTempFiles([
            'en' => ['-json' => ['String1' => 'Usor','String2'=>'Admin'] ],
            'nl' => ['-json' => ['String1' => 'Giraffe','String2'=>'Tiger'] ],
        ]);

        $this->artisan('langman:find', ['keyword' => 'admin']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/^| String2 (?:.*)Admin(?:.*)Tiger/', $this->consoleOutput());
        $this->assertStringNotContainsString('User', $this->consoleOutput());
        $this->assertStringNotContainsString('Giraffe', $this->consoleOutput());
    }
}
