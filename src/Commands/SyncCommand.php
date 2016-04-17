<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Themsaid\Langman\Manager;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:sync';

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
        $this->info('Reading translation keys from views...');

        $translationFiles = $this->manager->files();

        // An array of all translation keys as found in project files.
        $allViewsKeys = $this->manager->collectFromFiles();

        foreach ($translationFiles as $fileName => $languages) {
            foreach ($languages as $languageKey => $path) {
                $fileContent = $this->manager->getFileContent($path);

                if (isset($allViewsKeys[$fileName])) {
                    $this->fillMissingKeys($allViewsKeys[$fileName], $fileName, $fileContent, $languageKey);
                }
            }
        }

        $this->info('Done!');
    }

    /**
     * Fill the missing keys with an empty string in the given file.
     *
     * @param array $keys
     * @param string $fileName
     * @param array $fileContent
     * @param string $languageKey
     * @return void
     */
    private function fillMissingKeys(array $keys, $fileName, array $fileContent, $languageKey)
    {
        $missingKeys = [];

        foreach (array_diff($keys, array_keys(array_dot($fileContent))) as $missingKey) {
            $missingKeys[$missingKey] = [$languageKey => ''];

            $this->output->writeln("\"<fg=yellow>{$fileName}.{$missingKey}.{$languageKey}</>\" was added.");
        }

        $this->manager->fillKeys(
            $fileName,
            $missingKeys
        );
    }
}
