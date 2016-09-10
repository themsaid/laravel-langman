<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Themsaid\Langman\Manager;
use Themsaid\Langman\Support\Arr;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:import
        {filename? : Filename inside Langman Exports directory (storage/langman-exports).}
        {--P|path= : The path to Excel file relative to base path.}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Generates your language files from an Excel file';

    /**
     * The Languages manager instance.
     *
     * @var \Themsaid\LangMan\Manager
     */
    private $manager;

    /**
     * Array of files grouped by filename.
     *
     * @var array
     */
    protected $files;

    /**
     * ListCommand constructor.
     *
     * @param  \Themsaid\LangMan\Manager $manager
     * @return void
     */
    public function __construct(Manager $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $excelFileContents = $this->getExcelFileContents();

        if (is_null($excelFileContents)) {
            $this->error('No such file found.');
            return;
        }

        $filesToBeChanged = join("\n\t", array_keys(array_first($excelFileContents)));

        if (! $this->confirm("The following files will be overridden: \n\t" . $filesToBeChanged . "\nAre you sure?")) {
            $this->line('No files changed. Closing.');
            return;
        }

        $this->writeToLangFiles($excelFileContents);

        $this->info('Import complete.');
    }

    /**
     * Gets the user chosen excel file content.
     *
     * @return array
     */
    protected function getExcelFileContents()
    {
        $filePath = $this->getPathFromUserArgs();

        if (! file_exists($filePath)) {
            return null;
        }

        return $this->readExcelFileContents($filePath);
    }

    /**
     * Gets file path from user passed argument and option.
     *
     * @return string
     */
    protected function getPathFromUserArgs()
    {
        if (! is_null($fileName = $this->argument('filename'))) {
            return config('langman.exports_path') . DIRECTORY_SEPARATOR . $fileName;
        }

        if (is_null($this->option('path'))) {
            $this->error('No path specified.');
            exit();
        }

        return base_path($this->option('path'));
    }

    /**
     * Reads the actual Excel file from the specified path and returns
     * conent in an array grouped by directory and file names.
     *
     * @param  string $filePath
     * @return array
     */
    protected function readExcelFileContents($filePath)
    {
        $excelObj = \PHPExcel_IOFactory::load($filePath);
        $rows = $excelObj->getActiveSheet()->toArray('', true, true, true);

        $headerRow = array_shift($rows);
        $langDirs = $this->extractLangages($headerRow);

        $groupedByDirName = [];

        foreach ($langDirs as $index => $langDir) {
            $groupedByFileNames = [];
            $trans = [];
            $langDirName = '';

            foreach ($rows as $langRow) {
                // Override PHPExcel's column based key array into regular numbered key array
                $langRow = array_values($langRow);

                if ($langDirName != '' && $langDirName != $langRow[0]) {
                    $trans = [];
                }

                $langDirName = $langRow[0];
                $langKey = $langRow[1];

                $langIndex = $index+2;
                $trans[$langKey] = $langRow[$langIndex];

                $groupedByFileNames[$langDirName] = $trans;
            }

            $groupedByDirName[$langDir] = $groupedByFileNames;
        }

        return $groupedByDirName;
    }

    /**
     * Extract available language locales from file rows.
     *
     * @param  array $rows
     * @return array
     */
    protected function extractLangages($header)
    {
        return array_values(array_slice($header, 2));
    }

    /**
     * Write the content to language files.
     *
     * @param  array $data
     * @return void
     */
    protected function writeToLangFiles($data)
    {
        foreach ($data as $langDirName => $langDirContent) {
            $langDirPath = config('langman.path') . DIRECTORY_SEPARATOR . $langDirName;

            if (! file_exists($langDirPath)) {
                mkdir($langDirPath);
            }

            foreach ($langDirContent as $fileName => $fileContent) {
                $fileContent = Arr::unDot($fileContent);
                $fileContent = "<?php return \n" . var_export($fileContent, true) . ";\n";
                file_put_contents($langDirPath . DIRECTORY_SEPARATOR . $fileName .'.php', $fileContent);
            }
        }
    }
}
