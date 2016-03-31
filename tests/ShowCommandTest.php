<?php

class ShowCommandTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('langman.path', __DIR__.'/temp');
    }

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
}
