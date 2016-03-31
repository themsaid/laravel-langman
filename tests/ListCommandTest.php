<?php

use Mockery as m;

class ListCommandTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('langman.path', __DIR__.'/temp');
    }

    public function testCommandErrorOnFileNotFound()
    {
        $this->createTempFiles();

        $this->artisan('langman:list', ['file' => 'user']);

        $this->assertContains('Language file user.php not found!', $this->consoleOutput());
    }

    public function testCommandOutput()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['name' => 'Name', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['name' => 'Naam'];"],
        ]);

        $this->artisan('langman:list', ['file' => 'user']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/name(?:.*)Name(?:.*)Naam/', $this->consoleOutput());
        $this->assertRegExp('/age(?:.*)Age(?:.*)|(?: *)|/', $this->consoleOutput());
    }
}