<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Themsaid\Langman\Manager;

class MissingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:missing';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Find missing translation values and fill them.';

    /**
     * The Languages manager instance.
     *
     * @var \Themsaid\LangMan\Manager
     */
    private $manager;

    /**
     * Array of requested file in different languages.
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
        $this->info("Looking for missing translations...");

        $languages = $this->manager->languages();

        $missing = $this->getMissing($languages);

        $values = $this->collectValues($missing);

        $input = [];

        foreach ($values as $dottedKey => $value) {
            list($fileName, $key, $languageKey) = explode('.', $dottedKey);

            $input[$fileName][$key][$languageKey] = $value;

            $this->line("\"<fg=yellow>{$dottedKey}</>\" was set to \"<fg=yellow>{$value}</>\" successfully.");
        }

        foreach ($input as $fileName => $values) {
            $this->manager->fillKeys(
                $fileName,
                $values
            );
        }

        $this->info("Done!");
    }

    /**
     * Collect translation values from console via questions.
     *
     * @param array $missing
     * @return array
     */
    private function collectValues(array $missing)
    {
        $values = [];

        foreach ($missing as $missingKey) {
            $values[$missingKey['key']] = $this->ask(
                sprintf(
                    '%s translation:%s',
                    $missingKey['key'],
                    ($hint = $missingKey['hint']) ? " (Hint: $hint)" : ''
                )
            );
        }

        return $values;
    }

    /**
     * Get an array of keys that have missing values with a hint
     * from another language translation file if possible.
     *
     * ex: [ ['key' => 'product.color.nl', 'hint' => 'en = "color"'] ]
     *
     * @param array $languages
     * @return array
     */
    private function getMissing(array $languages)
    {
        $files = $this->manager->files();

        // Array of content of all files indexed by fileName.languageKey
        $filesResults = [];

        // The final output of the method
        $missing = [];

        // Here we collect the file results
        foreach ($files as $fileName => $languageFiles) {
            foreach ($languageFiles as $languageKey => $filePath) {
                $filesResults[$fileName][$languageKey] = $this->manager->getFileContent($filePath);
            }
        }

        $values = array_dot($filesResults);

        $emptyValues = array_filter($values, function ($value) {
            return $value == '';
        });

        // Adding all keys that has values = ''
        foreach ($emptyValues as $dottedValue => $emptyValue) {
            list($fileName, $languageKey, $key) = explode('.', $dottedValue);

            $missing[] = [
                'key' => implode('.', [$fileName, $key, $languageKey]),
                'hint' => "",
            ];
        }

        // Array of keys indexed by fileName.key, those keys we looked
        // at before so we save them in order for us to not look
        // at them again in a different language iteration.
        $searched = [];

        // Now we add keys that exists in a language but missing in
        // any other languages.
        foreach ($values as $key => $value) {
            list($fileName, $key, $languageKey) = explode('.', $key);

            if (isset($searched["{$fileName}.{$key}"])) {
                continue;
            }

            foreach ($languages as $languageName) {
                if (! isset($filesResults[$fileName][$languageName][$languageKey])) {
                    $missing[] = [
                        'key' => implode('.', [$fileName, $languageKey, $languageName]),
                        'hint' => "{$key} = \"{$value}\"",
                    ];
                }
            }

            $searched[] = "{$fileName}.{$key}";
        }

        return $missing;
    }
}