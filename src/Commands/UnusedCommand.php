<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Themsaid\Langman\Manager;

class UnusedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:unused';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Look for translations in views and update missing key in language files.';

    /**
     * The Languages manager instance.
     *
     * @var \Themsaid\LangMan\Manager
     */
    private $manager;

    /**
     * Command constructor.
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
        $translationFiles = $this->manager->files();

        $this->reportUnused($translationFiles);

        $this->info('Done!');
    }

    /**
     * Report unused keys in translation files
     *
     * @param $translationFiles
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return void
     */
    private function reportUnused($translationFiles)
    {
        $this->info('Finding unused keys...');

        // An array of all translation keys as found in project files.
        $allKeysInFiles = $this->manager->collectFromFiles();        

        foreach ($translationFiles as $fileName => $languages) {
            /// 
            foreach ($languages as $languageKey => $path) {
                $fileContent = $this->manager->getFileContent($path);

                if (isset($allKeysInFiles[$fileName])) {
                    
                    $missingKeys = array_diff(array_keys(array_dot($fileContent)), $allKeysInFiles[$fileName]);
                    foreach ($missingKeys as $i => $missingKey) {
                            $this->output->writeln("\"<fg=red>{$languageKey}.{$missingKey}</>\" never used.");
                    }
                }
            }
        }
    }    
}
