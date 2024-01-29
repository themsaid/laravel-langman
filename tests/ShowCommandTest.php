<?php

class ShowCommandTest extends TestCase
{
    public function testCommandErrorOnFileNotFound()
    {
        $this->createTempFiles();

        $this->artisan('langman:show', ['key' => 'user']);

        $this->assertContains('Language file user.php not found!', $this->consoleOutput());
    }

    public function testCommandOutputForFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['name' => 'Naam'];"],
        ]);

        $this->artisan('langman:show', ['key' => 'user']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/name(?:.*)Name(?:.*)Naam/', $this->consoleOutput());
        $this->assertRegExp('/age(?:.*)Age(?:.*)|(?: *)|/', $this->consoleOutput());
    }

    public function testCommandOutputForFileAndSpecificLanguages()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['name' => 'Naam'];"],
            'it_lang' => ['user' => "<?php\n return ['name' => 'Nome'];"],
        ]);

        $this->artisan('langman:show', ['key' => 'user', '--lang' => 'en,nl']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/name(?:.*)Name(?:.*)Naam/', $this->consoleOutput());
        $this->assertNotContains('Nome', $this->consoleOutput());
        $this->assertNotContains('it_lang', $this->consoleOutput());
    }

    public function testCommandOutputForPackageFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['weight' => 'weight'];", 'category' => ''],
            'nl' => ['user' => '', 'category' => ''],
            'vendor' => ['package' => ['en' => ['file' => "<?php\n return ['name' => 'name'];"], 'sp' => ['file' => "<?php\n return ['name' => 'something'];"]]],
        ]);

        $this->artisan('langman:show', ['key' => 'package::file']);

        $this->assertRegExp('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertRegExp('/name(?:.*)name(?:.*)something/', $this->consoleOutput());
    }

    public function testCommandOutputForFileWithNestedKeys()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => ['first' => 'first', 'last' => 'last']];"],
            'sp' => ['user' => "<?php\n return ['name' => ['first' => 'firstsp']];"],
        ]);

        $this->artisan('langman:show', ['key' => 'user']);

        $this->assertRegExp('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertRegExp('/name.first(?:.*)first(?:.*)firstsp/', $this->consoleOutput());
        $this->assertRegExp('/name.last(?:.*)last/', $this->consoleOutput());
    }

    public function testCommandOutputForKey()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'age' => 'Age', 'username' => 'uname'];"],
            'nl' => ['user' => "<?php\n return ['name' => 'Naam'];"],
        ]);

        $this->artisan('langman:show', ['key' => 'user.name']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/name(?:.*)Name(?:.*)Naam/', $this->consoleOutput());
        $this->assertNotContains('age', $this->consoleOutput());
        $this->assertNotContains('uname', $this->consoleOutput());
    }

    public function testCommandOutputForNestedKey()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['age' => 'age', 'name' => ['first' => 'first', 'last' => 'last']];"],
            'nl' => ['user' => "<?php\n return ['name' => ['first' => 'firstnl', 'last' => 'lastnl']];"],
        ]);

        $this->artisan('langman:show', ['key' => 'user.name.first']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/name.first(?:.*)first(?:.*)firstnl/', $this->consoleOutput());
        $this->assertNotContains('name.last', $this->consoleOutput());
        $this->assertNotContains('age', $this->consoleOutput());
    }

    public function testCommandOutputForSearchingParentKey()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['age' => 'age', 'name' => ['first' => 'first', 'last' => 'last']];"],
            'nl' => ['user' => "<?php\n return ['name' => ['first' => 'firstnl', 'last' => 'lastnl']];"],
        ]);

        $this->artisan('langman:show', ['key' => 'user.name']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/name.first(?:.*)first(?:.*)firstnl/', $this->consoleOutput());
        $this->assertRegExp('/name.last(?:.*)last(?:.*)lastnl/', $this->consoleOutput());
        $this->assertNotContains('age', $this->consoleOutput());
    }

    public function testCommandOutputForKeyOnCloseMatch()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'age' => 'Age', 'username' => 'uname'];"],
            'nl' => ['user' => "<?php\n return ['name' => 'Naam'];"],
        ]);

        $this->artisan('langman:show', ['key' => 'user.na', '-c' => null]);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/name(?:.*)Name(?:.*)Naam/', $this->consoleOutput());
        $this->assertRegExp('/username(?:.*)uname(?:.*)|(?: *)|/', $this->consoleOutput());
        $this->assertNotContains('age', $this->consoleOutput());
    }

    public function test_ignore_attributes_and_keys_with_empty_arrays()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name'];"],
            'nl' => ['user' => "<?php\n return ['name' => []];"],
        ]);

        $this->artisan('langman:show', ['key' => 'user']);
        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/name(?:.*)Name(?:.*)MISSING/', $this->consoleOutput());
    }
}
