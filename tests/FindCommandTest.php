<?php

use Illuminate\Support\Facades\Artisan;

class FindCommandTest extends TestCase
{
    public function testCommandErrorOnFilesNotFound()
    {
        array_map('unlink', glob(__DIR__.'/temp/*/*'));
        array_map('rmdir', glob(__DIR__.'/temp/*'));

        $this->createTempFiles();

        Artisan::call('langman:find', ['keyword' => 'ragnar']);

        $this->assertStringContainsString('No language files were found!', $this->consoleOutput());
    }

    public function testCommandOutputForFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['not_found' => 'User NoT fOunD', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['not_found' => 'something'];"],
            'sp' => ['user' => "<?php\n return ['else' => 'else'];"],
        ]);

        Artisan::call('langman:find', ['keyword' => 'not found']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/user\.not_found(?:.*)User NoT fOunD(?:.*)something/', $this->consoleOutput());
        $this->assertStringNotContainsString('age', $this->consoleOutput());
        $this->assertStringNotContainsString('else', $this->consoleOutput());
    }

    public function testCommandOutputForFileWithNestedKeys()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['missing' => ['not_found' => 'user not found'], 'jarl_borg' => 'flying'];"],
            'sp' => ['user' => "<?php\n return ['missing' => ['not_found' => 'sp']];"],
        ]);

        Artisan::call('langman:find', ['keyword' => 'not found']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/user\.missing\.not_found(?:.*)user not found(?:.*)sp/', $this->consoleOutput());
        $this->assertStringNotContainsString('jarl_borg', $this->consoleOutput());
    }

    public function testCommandOutputForPackage()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['weight' => 'weight'];", 'category' => ''],
            'nl' => ['user' => '', 'category' => ''],
            'vendor' => ['package' => ['en' => ['file' => "<?php\n return ['not_found' => 'file not found here'];"], 'sp' => ['file' => "<?php\n return ['not_found' => 'something'];"]]],
        ]);

        Artisan::call('langman:find', ['keyword' => 'not found', '--package' => 'package']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/package::file\.not_found(?:.*)file not found here(?:.*)something/', $this->consoleOutput());
        $this->assertStringNotContainsString('weight', $this->consoleOutput());
    }
}
