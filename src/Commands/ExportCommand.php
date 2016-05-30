<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
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
    protected $signature = 'langman:export {export-to=csv}';

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
     * Array of files grouped by filename.
     *
     * @var array
     */
    protected $files;

    /**
     * ListCommand constructor.
     *
     * @param \Themsaid\LangMan\Manager $manager
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
        $csv = Writer::createFromFileObject(new \SplFileObject(storage_path('somefile.csv'), 'w'));

        $header = array_merge(['Language File', 'Key'], $this->manager->languages());
        $csv->insertOne($header);

        $langFiles = $this->manager->files();

        $langArray = [];

        $filesContent = [];

        foreach($langFiles as $langFileName => $langFilePath) {
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
                    } catch(\ErrorException $ex) {
                        $row[] = '';
                    }
                }
                $content[] = $row;
            }
        }
//        dd($content);
        $csv->insertAll($content);

        $csv->output();

        $this->info('CSV file generated successfully.');
    }
}
