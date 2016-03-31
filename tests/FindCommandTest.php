<?php

class FindCommandTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('langman.path', __DIR__.'/temp');
    }

    public function testCommandErrorOnFilesNotFound()
    {
        $this->createTempFiles();

        $this->artisan('langman:find', ['keyword' => 'ragnar']);

        $this->assertContains('No language files were found!', $this->consoleOutput());
    }

    public function testCommandOutputForFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['not_found' => 'user not found', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['not_found' => 'something'];"],
        ]);

        $this->artisan('langman:find', ['keyword' => 'not found']);

        $this->assertRegExp('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertRegExp('/user\.not_found(?:.*)user not found/', $this->consoleOutput());
        $this->assertNotContains('age', $this->consoleOutput());
        $this->assertNotContains('something', $this->consoleOutput());
    }
}
