<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
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
        $translationFiles = $this->manager->files();

        $this->syncKeysFromFiles($translationFiles);

        $this->syncKeysBetweenLanguages($translationFiles);

        $this->info('Done!');
    }

    /**
     * Synchronize keys found in project files but missing in languages.
     *
     * @param $translationFiles
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return void
     */
    private function syncKeysFromFiles($translationFiles)
    {
        $this->info('Reading translation keys from files...');

        // An array of all translation keys as found in project files.
        $allKeysInFiles = $this->manager->collectFromFiles();

        foreach ($translationFiles as $fileName => $languages) {
            foreach ($languages as $languageKey => $path) {
                $fileContent = $this->manager->getFileContent($path);

                if (isset($allKeysInFiles[$fileName])) {
                    $missingKeys = array_diff($allKeysInFiles[$fileName], array_keys(array_dot($fileContent)));

                    foreach ($missingKeys as $i => $missingKey) {
                        if (Arr::has($fileContent, $missingKey)) {
                            unset($missingKeys[$i]);
                        }
                    }

                    $this->fillMissingKeys($fileName, $missingKeys, $languageKey);
                }
            }
        }
    }

    /**
     * Fill the missing keys with an empty string in the given file.
     *
     * @param string $fileName
     * @param array $foundMissingKeys
     * @param string $languageKey
     * @return void
     */
    private function fillMissingKeys($fileName, array $foundMissingKeys, $languageKey)
    {
        $missingKeys = [];

        foreach ($foundMissingKeys as $missingKey) {
            $missingKeys[$missingKey] = [$languageKey => ''];

            $this->output->writeln("\"<fg=yellow>{$fileName}.{$missingKey}.{$languageKey}</>\" was added.");
        }

        $this->manager->fillKeys(
            $fileName,
            $missingKeys
        );
    }

    /**
     * Synchronize keys that exist in a language but not the other.
     *
     * @param $translationFiles
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return void
     */
    private function syncKeysBetweenLanguages($translationFiles)
    {
        $this->info('Synchronizing language files...');

        $filesResults = [];

        // Here we collect the file results
        foreach ($translationFiles as $fileName => $languageFiles) {
            foreach ($languageFiles as $languageKey => $filePath) {
                $filesResults[$fileName][$languageKey] = $this->manager->getFileContent($filePath);
            }
        }

        $values = Arr::dot($filesResults);

        $missing = $this->manager->getKeysExistingInALanguageButNotTheOther($values);

        foreach ($missing as &$missingKey) {
            list($file, $key) = explode('.', $missingKey, 2);

            list($key, $language) = explode(':', $key, 2);

            $this->fillMissingKeys($file, [$key], $language);
        }
    }
}
