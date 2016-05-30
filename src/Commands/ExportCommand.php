<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use League\Csv\Writer;
use Themsaid\Langman\Manager;
use Illuminate\Support\Str;

class ExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:export
        {--P|path= : The location where the CSV file should be exported.}';

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
        $path = $this->generateCsvFile($this->option('path'));

        $this->info('CSV file successfully generated in ' . $path .'.');
    }

    private function generateCsvFile($path = null)
    {
        $csvPath = $this->getCsvPath($path);

        $csv = Writer::createFromFileObject(new \SplFileObject($csvPath, 'w'));

        $header = $this->getHeaderContent();
        $content = $this->getBodyContent();

        $csv->insertOne($header);
        $csv->insertAll($content);

        $csv->output();

        return $csvPath;
    }

    private function getCsvPath($path)
    {
        $exportDir = is_null($path) ? config('langman.csv_path') : base_path($path);

        if (! $this->filesystem->exists($exportDir)) {
            $this->filesystem->makeDirectory($exportDir);
        }

        return $exportDir . '/' . $this->getDatePrefix() . '_langman.csv';
    }

    /*
     * Get the date prefix for the CSV file.
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the CSV header content.
     *
     * @return array
     */
    protected function getHeaderContent()
    {
        return array_merge(['Language File', 'Key'], $this->manager->languages());
    }

    /**
     * Get the CSV rows content.
     *
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getBodyContent()
    {
        $langFiles = $this->manager->files();

        $langArray = [];

        $filesContent = [];

        foreach ($langFiles as $langFileName => $langFilePath) {
            foreach ($langFilePath as $languageKey => $file) {
                foreach ($filesContent[$languageKey] = Arr::dot($this->manager->getFileContent($file)) as $key => $value) {
                    $langArray[$langFileName][$key]['key'] = $key;
                    $langArray[$langFileName][$key][$languageKey] = $value;
                }
            }
        }

        $content = [];

        foreach ($langArray as $langName => $langProps) {
            $langProps = array_values($langProps);

            foreach ($langProps as $langRow) {
                $row = [$langName];
                $row[] = $langRow['key'];

                foreach ($this->manager->languages() as $language) {
                    try {
                        if (is_array($langRow[$language])) {
                            $row[] = '';
                        } else {
                            $row[] = $langRow[$language];
                        }
                    } catch (\ErrorException $ex) {
                        $row[] = '';
                    }
                }
                $content[] = $row;
            }
        }

        return $content;
    }
}
