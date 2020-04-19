<?php

class ShowCommandTest extends TestCase
{
    public function testCommandErrorOnFileNotFound()
    {
        $this->createTempFiles();

        $this->artisan('langman:show', ['key' => 'user']);

        $this->assertStringContainsString('JSON language strings not found!', $this->consoleOutput());
    }

    public function testOptionCombination1()
    {
        $this->createTempFiles([
            "en" => ["-json" => []]
        ]);

        $this->artisan('langman:show', ['key' => 'user']);

        $this->assertStringContainsString("Displaying specific keys matching 'user' from JSON strings using equality match", $this->consoleOutput());
    }

    public function testOptionCombination2()
    {
        $this->createTempFiles([
            "en" => ["-json" => []]
        ]);

        $this->artisan('langman:show', ['key' => 'user', "--close"=>true]);

        $this->assertStringContainsString("Displaying specific keys matching 'user' from JSON strings using substring match", $this->consoleOutput());
    }

    public function testOptionCombination3()
    {
        $this->createTempFiles([
            "en" => ["-json" => []]
        ]);

        $this->artisan('langman:show', ['key' => 'user', "--close"=>true, "--unused"=>true]);

        $this->assertStringContainsString("Displaying specific unused keys matching 'user' from JSON strings using substring match", $this->consoleOutput());
    }

    public function testOptionCombination3b()
    {
        $this->createTempFiles([
            "en" => ["-json" => []]
        ]);

        $this->artisan('langman:show', ['key' => 'user', "--unused"=>true]);

        $this->assertStringContainsString("Displaying specific unused keys matching 'user' from JSON strings using equality match", $this->consoleOutput());
    }

    public function testOptionCombination4()
    {
        $this->createTempFiles([
            "en" => ["-json" => []]
        ]);

        $this->artisan('langman:show', ['key' => 'user.name']);

        $this->assertStringContainsString("Displaying specific keys matching 'name' from user using equality match", $this->consoleOutput());
    }

    public function testOptionCombination5()
    {
        $this->createTempFiles([
            "en" => ["-json" => []]
        ]);

        $this->artisan('langman:show', ['key' => 'user.name', "--close"=>true]);

        $this->assertStringContainsString("Displaying specific keys matching 'name' from user using substring match", $this->consoleOutput());
    }

    public function testOptionCombination6()
    {
        $this->createTempFiles([
            "en" => ["-json" => []]
        ]);

        $this->artisan('langman:show', ['key' => 'user.name', "--close"=>true, "--unused"=>true]);

        $this->assertStringContainsString("Displaying specific unused keys matching 'name' from user using substring match", $this->consoleOutput());
    }

    public function testOptionCombination6b()
    {
        $this->createTempFiles([
            "en" => ["-json" => []]
        ]);

        $this->artisan('langman:show', ['key' => 'user.name', "--unused"=>true]);

        $this->assertStringContainsString("Displaying specific unused keys matching 'name' from user using equality match", $this->consoleOutput());
    }

    public function testOptionCombination7()
    {
        $this->createTempFiles([
            "en" => ["-json" => [], 'user' => '<?php return []; ']
        ]);

        $this->artisan('langman:show', ['key' => 'user']);

        $this->assertStringContainsString("Displaying all keys from user", $this->consoleOutput());
    }

    public function testOptionCombination8()
    {
        $this->createTempFiles([
            "en" => ["-json" => [], 'user' => '<?php return []; ']
        ]);

        $this->artisan('langman:show', ['key' => 'user', '--close' => true]);

        $this->assertStringContainsString("Displaying specific keys matching 'user' from JSON strings using substring match", $this->consoleOutput());
    }

    public function testOptionCombination9()
    {
        $this->createTempFiles([
            "en" => ["-json" => [], 'user' => '<?php return []; ']
        ]);

        $this->artisan('langman:show', ['key' => 'user', '--close' => true, '--unused' => true]);

        $this->assertStringContainsString("Displaying specific unused keys matching 'user' from JSON strings using substring match", $this->consoleOutput());
    }

    public function testOptionCombination9b()
    {
        $this->createTempFiles([
            "en" => ["-json" => [], 'user' => '<?php return []; ']
        ]);

        $this->artisan('langman:show', ['key' => 'user', '--unused' => true]);

        $this->assertStringContainsString("Displaying all unused keys from user", $this->consoleOutput());
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

    public function testCommandOutputForJSON()
    {
        $this->createTempFiles([
            'en' => [ '-json' => ['String 1'=>'String 1', 'String 2'=>'String 2']],
            'nl' => [ '-json' => ['String 1'=>'Primo', 'String 3 with a very long text that is cut off at some point' => 'Tiero']],
        ]);

        $this->artisan('langman:show');

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/String 1(?:.*)String 1(?:.*)Primo/', $this->consoleOutput());
        $this->assertRegExp('/String 2(?:.*)String 2(?:.*)|(?: *)|/', $this->consoleOutput());
        $this->assertRegExp('/String 3 with a very long text that is cut off at some point(?:.*)|(?: *)|(?:.*)Tiero/', $this->consoleOutput());
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
        $this->assertStringNotContainsString('age', $this->consoleOutput());
        $this->assertStringNotContainsString('uname', $this->consoleOutput());
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
        $this->assertStringNotContainsString('name.last', $this->consoleOutput());
        $this->assertStringNotContainsString('age', $this->consoleOutput());
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
        $this->assertStringNotContainsString('age', $this->consoleOutput());
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
        $this->assertStringNotContainsString('age', $this->consoleOutput());
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
