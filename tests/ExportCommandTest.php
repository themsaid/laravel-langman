<?php

class ExportCommandTest extends TestCase
{
    public function testCommandExportCreatesExcelFileFromLangFiles()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return['address' => 'Address', 'contact' => ['cellphone' => 'Mobile']];"],
            'es' => ['user' => "<?php\n return['address' => 'Dirección', 'contact' => ['cellphone' => 'Movil']];"],
        ]);

        $this->artisan('langman:export');

        $exportedFilePath = $this->getExportedFilePath();

        $excelRows = $this->getExcelFileContents($exportedFilePath);

        $this->assertFileExists($exportedFilePath);
        $this->assertExcelRowEquals($excelRows[1], ['Language File', 'Key', 'en', 'es']);
        $this->assertExcelRowEquals($excelRows[2], ['user', 'address', 'Address', 'Dirección']);
        $this->assertExcelRowEquals($excelRows[3], ['user', 'contact.cellphone', 'Mobile', 'Movil']);
    }

    public function testCommandExportOnlyExportsSpecifiedFiles()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return['address' => 'Address'];", 'course' => "<?php\n return['start_date' => 'Start Date'];",],
            'es' => ['user' => "<?php\n return['address' => 'Dirección'];", 'course' => "<?php\n return['start_date' => 'Fecha De Inicio'];"],
        ]);

        $this->artisan('langman:export', ['--only' => 'course']);

        $exportedFilePath = $this->getExportedFilePath();

        $excelRows = $this->getExcelFileContents($exportedFilePath);

        $this->assertFileExists($exportedFilePath);

        // Always remember that first row is the header row,
        // it does not contain any language file content
        $this->assertCount(2, $excelRows);
        $this->assertTrue($this->excelContentContainsRow($excelRows, ['course', 'start_date', 'Start Date', 'Fecha De Inicio']));
        $this->assertFalse($this->excelContentContainsRow($excelRows, ['user', 'address', 'Address', 'Dirección']));
    }

    public function testOptionOnlySupportsCommaSeparatedNames()
    {
        $this->createTempFiles([
            'en' => [
                'user' => "<?php\n return['address' => 'Address'];",
                'course' => "<?php\n return['start_date' => 'Start Date'];",
                'product' => "<?php\n return['name' => 'Name', 'description' => 'Description'];"
            ],
            'es' => [
                'user' => "<?php\n return['address' => 'Dirección'];",
                'course' => "<?php\n return['start_date' => 'Fecha De Inicio'];",
                'product' => "<?php\n return['name' => 'Nombre', 'description' => 'Descripción'];"
            ],
        ]);

        $this->artisan('langman:export', ['--only' => 'user,product']);

        $exportedFilePath = $this->getExportedFilePath();

        $excelRows = $this->getExcelFileContents($exportedFilePath);

        $this->assertFileExists($exportedFilePath);
        $this->assertCount(4, $excelRows);
        $this->assertTrue($this->excelContentContainsRow($excelRows, ['user', 'address', 'Address', 'Dirección']));
        $this->assertTrue($this->excelContentContainsRow($excelRows, ['product', 'name', 'Name', 'Nombre']));
        $this->assertTrue($this->excelContentContainsRow($excelRows, ['product', 'description', 'Description', 'Descripción']));
        $this->assertFalse($this->excelContentContainsRow($excelRows, ['course', 'start_date', 'Start Date', 'Fecha De Inicio']));
    }

    public function testCommandExportExcludesSpecifiedFiles()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return['address' => 'Address'];", 'course' => "<?php\n return['start_date' => 'Start Date'];",],
            'es' => ['user' => "<?php\n return['address' => 'Dirección'];", 'course' => "<?php\n return['start_date' => 'Fecha De Inicio'];"],
        ]);

        $this->artisan('langman:export', ['--exclude' => 'user']);

        $exportedFilePath = $this->getExportedFilePath();

        $excelRows = $this->getExcelFileContents($exportedFilePath);

        $this->assertFileExists($exportedFilePath);

        $this->assertCount(2, $excelRows);
        $this->assertTrue($this->excelContentContainsRow($excelRows, ['course', 'start_date', 'Start Date', 'Fecha De Inicio']));
        $this->assertFalse($this->excelContentContainsRow($excelRows, ['user', 'address', 'Address', 'Dirección']));
    }

    public function testOptionExcludeSupportsCommaSeparatedNames()
    {
        $this->createTempFiles([
            'en' => [
                'user' => "<?php\n return['address' => 'Address'];",
                'course' => "<?php\n return['start_date' => 'Start Date'];",
                'product' => "<?php\n return['name' => 'Name', 'description' => 'Description'];"
            ],
            'es' => [
                'user' => "<?php\n return['address' => 'Dirección'];",
                'course' => "<?php\n return['start_date' => 'Fecha De Inicio'];",
                'product' => "<?php\n return['name' => 'Nombre', 'description' => 'Descripción'];"
            ],
        ]);

        $this->artisan('langman:export', ['--exclude' => 'user,product']);

        $exportedFilePath = $this->getExportedFilePath();

        $excelRows = $this->getExcelFileContents($exportedFilePath);

        $this->assertFileExists($exportedFilePath);
        $this->assertCount(2, $excelRows);
        $this->assertFalse($this->excelContentContainsRow($excelRows, ['user', 'address', 'Address', 'Dirección']));
        $this->assertFalse($this->excelContentContainsRow($excelRows, ['product', 'name', 'Name', 'Nombre']));
        $this->assertFalse($this->excelContentContainsRow($excelRows, ['product', 'description', 'Description', 'Descripción']));
        $this->assertTrue($this->excelContentContainsRow($excelRows, ['course', 'start_date', 'Start Date', 'Fecha De Inicio']));
    }

    public function testExcludeAndOnlyOptionCannotBeCombined()
    {
        $this->artisan('langman:export', ['--exclude' => 'somefile', '--only' => 'someanotherfile']);

        $this->assertContains('You cannot combine --only and --exclude options.', $this->consoleOutput());
    }

    protected function getExcelFileContents($exportedFilePath)
    {
        $excelObj = \PHPExcel_IOFactory::load($exportedFilePath);
        $rows = $excelObj->getActiveSheet()->toArray('', true, true, true);

        return $rows;
    }

    protected function assertExcelRowEquals($row, $content)
    {
        $columns = array_values($row);

        $this->assertEquals(count($columns), count($content));

        foreach ($columns as $index => $column) {
            $this->assertEquals($column, $content[$index]);
        }
    }

    protected function getExportedFilePath()
    {
        return $this->app['config']['langman.exports_path'] . '/' . date('Y_m_d_His') . '_langman.xlsx';
    }

    protected function excelContentContainsRow($excelRows, $row)
    {
        foreach ($excelRows as $excelRow) {
            $excelRow = array_values($excelRow);

            if ($excelRow == $row) {
                return true;
            }
        }

        return false;
    }
}
