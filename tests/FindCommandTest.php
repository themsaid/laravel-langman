<?php

class FindCommandTest extends TestCase
{
    public function testCommandErrorOnFilesNotFound()
    {
        array_map('unlink', glob(__DIR__.'/temp/*/*'));
        array_map('rmdir', glob(__DIR__.'/temp/*'));

        $this->createTempFiles();

        $this->artisan('langman:find', ['keyword' => 'ragnar']);

        $this->assertContains('No language files were found!', $this->consoleOutput());
    }

    public function testCommandOutputForFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['not_found' => 'user not found', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['not_found' => 'something'];"],
            'sp' => ['user' => "<?php\n return ['else' => 'else'];"],
        ]);

        $this->artisan('langman:find', ['keyword' => 'not found']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/user\.not_found(?:.*)user not found(?:.*)something/', $this->consoleOutput());
        $this->assertNotContains('age', $this->consoleOutput());
        $this->assertNotContains('else', $this->consoleOutput());
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
        $this->assertNotContains('jarl_borg', $this->consoleOutput());
    }
}
