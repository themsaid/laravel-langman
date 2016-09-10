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
            ['product', 'name', 'Name', 'Nombre'],
            ['product', 'description', 'Description', 'Descripción'],
            ['course', 'start_date', 'Start Date', 'Fecha De Inicio'],
            ['user', 'address', 'Address', 'Dirección'],
            ['user', 'education.major', 'Major', 'Importante'],
            ['user', 'education.minor', 'Minor', 'Menor']
        ]);

        $filename = basename($path);

        $filesToBeChanged = join("\n\t", ['product', 'course', 'user']);

        $command = m::mock('\Themsaid\Langman\Commands\ImportCommand[confirm]', [$manager]);
        $command->shouldReceive('confirm')->once()
            ->with("The following files will be overridden: \n\t" . $filesToBeChanged . "\nAre you sure?")->andReturn(true);

        $this->app['artisan']->add($command);
        $this->artisan('langman:import', ['filename' => $filename]);

        $langPath = $this->app['config']['langman.path'];

        $this->assertFileExists($langPath . '/en/product.php');
        $this->assertFileExists($langPath . '/en/course.php');
        $this->assertFileExists($langPath . '/en/user.php');
        $this->assertFileExists($langPath . '/es/product.php');
        $this->assertFileExists($langPath . '/es/course.php');
        $this->assertFileExists($langPath . '/es/user.php');
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