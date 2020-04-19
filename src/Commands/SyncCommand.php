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

        // reread the files in case we created new ones while syncing
        $translationFiles = $this->manager->files();

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
        $languages = $this->manager->languages();

        foreach ($allKeysInFiles as $file=>$labels) {
            foreach ($languages as $lang) {
                if (!isset($translationFiles[$file])) {
                    if ($file === "-json") {
                        $this->info('Found json translation keys');
                    } else {
                        $this->info('Found translation keys for new file '.$file);
                    }
                    $translationFiles[$file]=[];
                }
                if (!isset($translationFiles[$file][$lang])) {
                    if ($file === "-json") {
                        $this->info('Creating new JSON translation file for locale '.$lang);
                    } else {
                        $this->info('Creating new translation key file '.$file.' for locale '.$lang);
                    }
                    $this->manager->createFile($file, $lang);
                    $translationFiles[$file][$lang]=$this->manager->createFileName($file, $lang);
                }

                $path = $translationFiles[$file][$lang];
                $fileContent = $this->manager->getFileContent($path);

                $missingKeys = array_diff($labels, array_keys(Arr::dot($fileContent)));

                // remove all keys that are in the translation, but not in any view
                foreach ($missingKeys as $i => $missingKey) {
                    if (Arr::has($fileContent, $missingKey)) {
                        unset($missingKeys[$i]);
                    }
                }

                $this->fillMissingKeys($file, $missingKeys, $lang);
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

            if ($fileName == "-json") {
                $this->output->writeln("\"<fg=yellow>JSON {$languageKey}:{$missingKey}</>\" was added.");
            } else {
                $this->output->writeln("\"<fg=yellow>{$languageKey}.{$fileName}.{$missingKey}</>\" was added.");
            }
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
