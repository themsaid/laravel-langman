<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Themsaid\Langman\Manager;

class ExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:export
        {--P|path= : The location where the exported file should be exported.}
        {--only= : Specify the file(s) you want to export to Excel}
        {--exclude= : File(s) you do not want to export to Excel}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Generates a Excel file from your language files';

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
        $path = $this->generateExcelFile($this->option('path'));

        $this->info('Excel file successfully generated in ' . $path .'.');
    }

    /**
     * Generates an Excel file from translations files and putting it in
     * the given path.
     *
     * @param  string|null $path
     * @return string
     */
    protected function generateExcelFile($path = null)
    {
        $filePath = $this->getFilePath($path);

        $userSelectedFiles = $this->filterFilesForExport();

        $this->writeContentsToFile($this->getHeaderContent(), $this->getBodyContent($userSelectedFiles), $filePath);

        return $filePath;
    }

    /**
     * Filter files based on user options
     *
     * @return array|string
     */
    protected function filterFilesForExport()
    {
        if (! is_null($this->option('only')) && ! is_null($this->option('exclude'))) {
            $this->error('You cannot combine --only and --exclude options. Please use one of them.');
            exit();
        }

        $onlyFiles = [];

        if (! is_null($this->option('only'))) {
            $onlyFiles = array_keys($this->manager->files(explode(',', $this->option('only'))));
        }

        if (! is_null($this->option('exclude'))) {
            $excludeFiles = explode(',', $this->option('exclude'));
            $onlyFiles = array_diff(array_keys($this->manager->files()), $excludeFiles);
        }

        return $onlyFiles;
    }

    /**
     * Creating a Excel file from the given content.
     *
     * @param  $header
     * @param  $content
     * @param  $filepath
     * @return void
     */
    protected function writeContentsToFile($header, $content, $filepath)
    {
        array_unshift($content, $header);

        $excelObj = new \PHPExcel();
        $excelObj->getProperties()
            ->setTitle('Laravel Langman Exported Language File')
            ->setSubject('Laravel Langman Exported Language File')
            ->setCreator('Laravel Langman');

        foreach ($content as $record) {
            dd($record);
        }

        $writer = \PHPExcel_IOFactory::createWriter($excelObj, 'Excel2007');
        $writer->save($filepath);
//
//        $writer = new PHPExcel();
//        $writer->save($filepath);
//        $file = fopen($filepath, 'w');
//        $csvText = '';
//
//        foreach($content as $csvRecord) {
//            // Fields containing line breaks (CRLF), double quotes, and commas should be
//            // enclosed in double-quotes. We need this to escape commas and other
//            // special CSV characters.
//            $csvRecord = array_map(function($element) {
//                return '"' . $element . '"';
//            }, $csvRecord);
//
//            // Here we create a CSV record from an array record.
//            $csvText .= implode(',', $csvRecord) . "\n";
//        }
//
//        // These lines handle encoding issues. They make sure that a CSV file
//        // is properly rendered in most of the CSV reader tools.
//        mb_convert_encoding($csvText, 'UTF-16LE', 'UTF-8');
//        fprintf($file, "\xEF\xBB\xBF");
//
//        fputs($file, $csvText);
//        fclose($file);
    }

    /**
     * Get the file path for the CSV file.
     *
     * @param  $path
     * @return string
     */
    protected function getFilePath($path)
    {
        $exportDir = is_null($path) ? config('langman.csv_path') : base_path($path);

        if (! $this->filesystem->exists($exportDir)) {
            $this->filesystem->makeDirectory($exportDir);
        }

        return $exportDir . '/' . $this->getDatePrefix() . '_langman.xlsx';
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
    protected function getBodyContent($files)
    {
        $langFiles = $this->manager->getFilesContentGroupedByFilenameAndKey($files);
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
