<?php

use Illuminate\Support\Facades\Artisan;

class ShowCommandTest extends TestCase
{
    public function testCommandErrorOnFileNotFound()
    {
        $this->createTempFiles();

        Artisan::call('langman:show', ['key' => 'user']);

        $this->assertStringContainsString('Language file user.php not found!', $this->consoleOutput());
    }

    public function testCommandOutputForFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['name' => 'Naam'];"],
        ]);

        Artisan::call('langman:show', ['key' => 'user']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name(?:.*)Name(?:.*)Naam/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/age(?:.*)Age(?:.*)|(?: *)|/', $this->consoleOutput());
    }

    public function testCommandOutputForFileAndSpecificLanguages()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['name' => 'Naam'];"],
            'it_lang' => ['user' => "<?php\n return ['name' => 'Nome'];"],
        ]);

        Artisan::call('langman:show', ['key' => 'user', '--lang' => 'en,nl']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name(?:.*)Name(?:.*)Naam/', $this->consoleOutput());
        $this->assertStringNotContainsString('Nome', $this->consoleOutput());
        $this->assertStringNotContainsString('it_lang', $this->consoleOutput());
    }

    public function testCommandOutputForPackageFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['weight' => 'weight'];", 'category' => ''],
            'nl' => ['user' => '', 'category' => ''],
            'vendor' => ['package' => ['en' => ['file' => "<?php\n return ['name' => 'name'];"], 'sp' => ['file' => "<?php\n return ['name' => 'something'];"]]],
        ]);

        Artisan::call('langman:show', ['key' => 'package::file']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name(?:.*)name(?:.*)something/', $this->consoleOutput());
    }

    public function testCommandOutputForFileWithNestedKeys()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => ['first' => 'first', 'last' => 'last']];"],
            'sp' => ['user' => "<?php\n return ['name' => ['first' => 'firstsp']];"],
        ]);

        Artisan::call('langman:show', ['key' => 'user']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name.first(?:.*)first(?:.*)firstsp/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name.last(?:.*)last/', $this->consoleOutput());
    }

    public function testCommandOutputForKey()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'age' => 'Age', 'username' => 'uname'];"],
            'nl' => ['user' => "<?php\n return ['name' => 'Naam'];"],
        ]);

        Artisan::call('langman:show', ['key' => 'user.name']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name(?:.*)Name(?:.*)Naam/', $this->consoleOutput());
        $this->assertStringNotContainsString('age', $this->consoleOutput());
        $this->assertStringNotContainsString('uname', $this->consoleOutput());
    }

    public function testCommandOutputForNestedKey()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['age' => 'age', 'name' => ['first' => 'first', 'last' => 'last']];"],
            'nl' => ['user' => "<?php\n return ['name' => ['first' => 'firstnl', 'last' => 'lastnl']];"],
        ]);

        Artisan::call('langman:show', ['key' => 'user.name.first']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name.first(?:.*)first(?:.*)firstnl/', $this->consoleOutput());
        $this->assertStringNotContainsString('name.last', $this->consoleOutput());
        $this->assertStringNotContainsString('age', $this->consoleOutput());
    }

    public function testCommandOutputForSearchingParentKey()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['age' => 'age', 'name' => ['first' => 'first', 'last' => 'last']];"],
            'nl' => ['user' => "<?php\n return ['name' => ['first' => 'firstnl', 'last' => 'lastnl']];"],
        ]);

        Artisan::call('langman:show', ['key' => 'user.name']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name.first(?:.*)first(?:.*)firstnl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name.last(?:.*)last(?:.*)lastnl/', $this->consoleOutput());
        $this->assertStringNotContainsString('age', $this->consoleOutput());
    }

    public function testCommandOutputForKeyOnCloseMatch()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'age' => 'Age', 'username' => 'uname'];"],
            'nl' => ['user' => "<?php\n return ['name' => 'Naam'];"],
        ]);

        Artisan::call('langman:show', ['key' => 'user.na', '-c' => null]);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name(?:.*)Name(?:.*)Naam/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/username(?:.*)uname(?:.*)|(?: *)|/', $this->consoleOutput());
        $this->assertStringNotContainsString('age', $this->consoleOutput());
    }

    public function test_ignore_attributes_and_keys_with_empty_arrays()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name'];"],
            'nl' => ['user' => "<?php\n return ['name' => []];"],
        ]);

        Artisan::call('langman:show', ['key' => 'user']);
        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/name(?:.*)Name(?:.*)MISSING/', $this->consoleOutput());
    }
}
