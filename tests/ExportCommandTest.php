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

        $this->assertFileExists($exportedFilePath);
        $this->assertExcelRowEquals($excelRows[1], ['Language File', 'Key', 'en', 'es']);
        $this->assertExcelRowEquals($excelRows[2], ['user', 'address', 'Address', 'Dirección']);
        $this->assertExcelRowEquals($excelRows[3], ['user', 'contact.cellphone', 'Mobile', 'Movil']);
    }

    protected function getExcelFileContents($exportedFilePath)
    {
        $excelObj = \PHPExcel_IOFactory::load($exportedFilePath);
        $rows = $excelObj->getActiveSheet()->toArray('', true, true, true);

        return $rows;
    }

    protected function assertExcelRowEquals($row, $content) {
        $columns = array_values($row);

        $this->assertEquals(count($columns), count($content));

        foreach ($columns as $index => $column) {
            $this->assertEquals($column, $content[$index]);
        }
    }
}