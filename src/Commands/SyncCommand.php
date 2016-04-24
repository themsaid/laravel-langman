<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Themsaid\Langman\Manager;
use Themsaid\Langman\DispatcherTrait;

class SyncCommand extends Command
{
    use DispatcherTrait;

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

        // An array of all translation keys as found in views files.
        $allViewsKeys = $this->manager->collectFromViews();

        $filledKeys = [];

        foreach ($translationFiles as $fileName => $languages) {
            foreach ($languages as $languageKey => $path) {
                $fileContent = $this->manager->getFileContent($path);

                if (isset($allViewsKeys[$fileName])) {
                    $filledKeys[$fileName][$languageKey] = $this->getDiffs($allViewsKeys[$fileName], $fileContent);
                    $this->fillMissingKeys($allViewsKeys[$fileName], $fileName, $fileContent, $languageKey);
                }
            }
        }

        $this->eventDispatch($translationFiles, $allViewsKeys, $filledKeys);

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

        foreach ($this->getDiffs($keys, $fileContent) as $missingKey) {
            $missingKeys[$missingKey] = [$languageKey => ''];

            $this->output->writeln("\"<fg=yellow>{$fileName}.{$missingKey}.{$languageKey}</>\" was added.");
        }

        $this->manager->fillKeys(
            $fileName,
            $missingKeys
        );
    }

    /**
     * Find the diffrent keys in view keys and translation file
     *
     * @param  array  $keys        Found in view files
     * @param  array  $fileContent Translation file
     * @return array
     */
    private function getDiffs(array $keys, array $fileContent)
    {
        return array_diff($keys, array_keys($fileContent));
    }
}
