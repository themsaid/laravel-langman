<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
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
        {filename? : Filename inside Langman Excel directory.}
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
     * The Languages manager instance.
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $filesystem;

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
     * @param  \Illuminate\Contracts\Filesystem\Filesystem
     * @return void
     */
    public function __construct(Manager $manager, Filesystem $filesystem)
    {
        parent::__construct();

        $this->manager = $manager;
        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $excelFileContents = $this->getExcelFileContents();

        $filesToBeChanged = join("\n\t", array_keys(array_first($excelFileContents)));

        if (! $this->confirm("The following files will be overridden: \n\t" . $filesToBeChanged . "\nAre you sure?")) {
            $this->line('No files changed. Closing.');
            exit();
        }

        $this->writeToLangFiles($csvFileContents);

        $this->info('Import complete.');
    }

    protected function getExcelFileContents()
    {
        if (is_null($this->option('path'))) {
            if (is_null($fileName = $this->argument('filename'))) {
                $this->error('No path specified.');
                exit();
            } else {
                $filePath = config('langman.exports_path') .DIRECTORY_SEPARATOR. $fileName;
            }
        } else {
            $filePath = base_path($this->option('path'));
        }

        if (! file_exists($filePath)) {
            $this->error('No such file found: ' . $filePath);
            exit();
        }

        return $this->readExcelFileContents($filePath);
    }

    protected function readExcelFileContents($filePath)
    {
        $excelObj = \PHPExcel_IOFactory::load($filePath);

        $rows = $excelObj->getActiveSheet()->toArray('', true, true, true);

        foreach ($rows as $row) {
            dd(array_values($row));
        }
    }

    protected function writeToLangFiles($data)
    {
        foreach ($data as $langDirName => $langDirContent) {
            $langDirPath = config('langman.path') . DIRECTORY_SEPARATOR . $langDirName;

            if (! $this->filesystem->exists($langDirPath)) {
                $this->filesystem->makeDirectory($langDirPath);
            }

            foreach ($langDirContent as $fileName => $fileContent) {
                $fileContent = Arr::unDot($fileContent);
                $fileContent = "<?php return \n" . var_export($fileContent, true) . ";\n";
                file_put_contents($langDirPath . DIRECTORY_SEPARATOR . $fileName .'.php', $fileContent);
            }
        }
    }
}
