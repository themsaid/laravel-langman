<?php

class ExportCommandTest extends TestCase
{
    public function testCreatesExcelFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return['address' => 'Address', 'contact' => ['cellphone' => 'Mobile']];"],
            'es' => ['user' => "<?php\n return['address' => 'Dirección', 'contact' => ['cellphone' => 'Movil']];"],
        ]);

        $this->artisan('langman:export');

        $exportedFilePath = $this->app['config']['langman.exports_path'] . '/' . date('Y_m_d_His') . '_langman.xlsx';

        $excelRows = $this->getExcelFileContents($exportedFilePath);

        $headerRow = $excelRows[1];
        $contentRows = [$excelRows[2], $excelRows[3]];

        $this->assertFileExists($exportedFilePath);
        $this->assertHeaderRow($headerRow);
        $this->assertContentRows($contentRows);
    }

    protected function getExcelFileContents($exportedFilePath)
    {
        $excelObj = \PHPExcel_IOFactory::load($exportedFilePath);
        $rows = $excelObj->getActiveSheet()->toArray('', true, true, true);

        return $rows;
    }

    protected function assertHeaderRow($headerRow)
    {
        $this->assertEquals($headerRow['A'], 'Language File');
        $this->assertEquals($headerRow['B'], 'Key');
        $this->assertEquals($headerRow['C'], 'en');
        $this->assertEquals($headerRow['D'], 'es');
    }

    protected function assertContentRows($contentRows)
    {
        $row1 = $contentRows[0];
        $this->assertEquals($row1['A'], 'user');
        $this->assertEquals($row1['B'], 'address');
        $this->assertEquals($row1['C'], 'Address');
        $this->assertEquals($row1['D'], 'Dirección');

        $row2 = $contentRows[1];
        $this->assertEquals($row2['A'], 'user');
        $this->assertEquals($row2['B'], 'contact.cellphone');
        $this->assertEquals($row2['C'], 'Mobile');
        $this->assertEquals($row2['D'], 'Movil');
    }
}