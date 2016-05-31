<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use League\Csv\Writer;
use League\Csv\Reader;
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

    /**
     * Generates a CSV file from translations files and putting it in
     * the given path.
     *
     * @param  string|null $path
     * @return string
     */
    private function generateCsvFile($path = null)
    {
        $csvPath = $this->getCsvPath($path);

        $this->writeContentToCsvFile($this->getHeaderContent(), $this->getBodyContent(), $csvPath);

        return $csvPath;
    }

    /**
     * Creating a CSV file from the given content into the given file.
     *
     * @param  $header
     * @param  $content
     * @param  $filepath
     * @return void
     */
    protected function writeContentToCsvFile($header, $content, $filepath)
    {
        array_unshift($content, $header);

        $file = fopen($filepath, 'w');
        $csvText = '';

        foreach($content as $csvRecord) {
            // Fields containing line breaks (CRLF), double quotes, and commas should be
            // enclosed in double-quotes. We need this to escape commas and other
            // special CSV characters.
            $csvRecord = array_map(function($element) {
                return '"' . $element . '"';
            }, $csvRecord);

            // Here we create a CSV record from an array record.
            $csvText .= implode(',', $csvRecord) . "\n";
        }

        // These lines handle encoding issues. They make sure that a CSV file
        // is properly rendered in most of the CSV reader tools.
        mb_convert_encoding($csvText, 'UTF-16LE', 'UTF-8');
        fprintf($file, "\xEF\xBB\xBF");

        fputs($file, $csvText);
        fclose($file);
    }

    /**
     * Get the file path for the CSV file.
     *
     * @param  $path
     * @return string
     */
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
        $langFiles = $this->manager->getFilesContentGroupedByFilenameAndKey();
        $content = [];

        foreach ($langFiles as $langFileName => $langProps) {
            foreach ($langProps as $key => $translations) {
                $row = [$langFileName, $key];

                foreach ($this->manager->languages() as $language) {
                    // If an UndefinedIndex Exception was thrown, it means that $key
                    // does not have translation in the $language, so we will
                    // handle it by just assigning it to an empty string
                    try {
                        // If a translation is just an array (empty), it means that it doesn't have
                        // any translation so we will skip it by assigning it an empty string.
                        $row[] = is_array($translations[$language]) ? '' : $translations[$language];
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
