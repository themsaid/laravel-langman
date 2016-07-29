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
        {filename? : Filename inside Langman CSV directory}
        {--P|path= : The location where the CSV file is located.}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Generates a CSV file from your language files';

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
        $csvFileContents = $this->getCsvFileContents();

        $filesToBeChanged = join("\n\t", array_keys(array_first($csvFileContents)));

        if (! $this->confirm("The following files will be overridden: \n\t" . $filesToBeChanged . "\nAre you sure?")) {
            $this->line('No files changed.');
            exit();
        }

        $this->writeToLangFiles($csvFileContents);

        $this->info('Import complete.');
    }

    protected function getCsvFileContents()
    {
        if (is_null($this->option('path'))) {
            if (is_null($fileName = $this->argument('filename'))) {
                $this->error('No path specified.');
                exit();
            } else {
                $filePath = config('langman.csv_path') .DIRECTORY_SEPARATOR. $fileName;
            }
        } else {
            $filePath = base_path($this->option('path'));
        }

        if (! file_exists($filePath)) {
            $this->error('No such file found: ' . $filePath);
            exit();
        }

        return $this->readCsvFileContents($filePath);
    }

    protected function readCsvFileContents($filePath)
    {
        $csv = array_map('str_getcsv', file($filePath));

        // Get header content
        $langDirs = array_slice(array_shift($csv), 2);

        $groupedByDirName = [];

        foreach ($langDirs as $index => $langDir) {
            $groupedByFileNames = [];
            $trans = [];
            $langDirName = '';
            foreach ($csv as $langRow) {
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
