<?php

use Mockery as m;
use Themsaid\Langman\Manager;

class ImportCommandTest extends TestCase
{
    public function testCommandImportsContentFromExcelToLangFiles()
    {
        $manager = $this->app[Manager::class];

        $path = $this->createTempExcelFile([
            ['Language File', 'Key', 'en', 'es'],
            ['course', 'start_date', 'Start Date', 'Fecha De Inicio'],
            ['user', 'address', 'Address', 'Dirección'],
            ['user', 'education.major', 'Major', 'Importante'],
            ['user', 'education.minor', 'Minor', 'Menor']
        ]);

        $filename = basename($path);

        $filesToBeChanged = join("\n\t", ['course', 'user']);

        $command = m::mock('\Themsaid\Langman\Commands\ImportCommand[confirm]', [$manager]);
        $command->shouldReceive('confirm')->once()
            ->with("The following files will be overridden: \n\t" . $filesToBeChanged . "\nAre you sure?")->andReturn(true);

        $this->app['artisan']->add($command);
        $this->artisan('langman:import', ['filename' => $filename]);

        $langPath = $this->app['config']['langman.path'];

        $userEnglish = include $langPath . '/en/user.php';
        $userSpanish = include $langPath . '/es/user.php';
        $courseEnglish = include $langPath . '/en/course.php';
        $courseSpanish = include $langPath . '/es/course.php';

        $this->assertContains('Import complete', $this->consoleOutput());

        // Assert user.php content
        $this->assertEquals($userEnglish['address'], 'Address');
        $this->assertEquals($userSpanish['address'], 'Dirección');

        // Assert dotted keys
        $this->assertEquals($userEnglish['education']['major'], 'Major');
        $this->assertEquals($userEnglish['education']['minor'], 'Minor');
        $this->assertEquals($userSpanish['education']['major'], 'Importante');
        $this->assertEquals($userSpanish['education']['minor'], 'Menor');

        // Assert course.php content
        $this->assertEquals($courseEnglish['start_date'], 'Start Date');
        $this->assertEquals($courseSpanish['start_date'], 'Fecha De Inicio');
    }

    public function testCommandShowsErrorIfFileNotFound()
    {
        $this->artisan('langman:import', ['filename' => 'nofile.xlsx']);

        $this->assertContains('No such file found', $this->consoleOutput());
    }

    /**
     * Create a temporary excel file.
     *
     * @param  $contentArray
     * @return string
     */
    protected function createTempExcelFile($contentArray)
    {
        $excelObj = new PHPExcel();

        $excelObj->getActiveSheet()->fromArray($contentArray, '');

        $objWriter = PHPExcel_IOFactory::createWriter($excelObj, 'Excel2007');
        $filePath = $this->app['config']['langman.exports_path'] . '/translations.xlsx';
        $objWriter->save($filePath);

        return $filePath;
    }
}
