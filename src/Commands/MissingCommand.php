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
        $languages = $this->manager->languages();

        $missing = $this->getMissing($languages);

        $values = $this->collectValues($missing);

        foreach ($values as $dottedKey => $value) {
            list($fileName, $key, $languageKey) = explode('.', $dottedKey);

            $this->manager->fillKey(
                $fileName,
                $key,
                [$languageKey => $value]
            );

            $this->info("{$fileName}.{$key}.{$languageKey} was set to \"{$value}\" successfully.");
        }
    }

    /**
     * Collect translation values from console via questions.
     *
     * @param array $missing
     * @return array
     */
    private function collectValues(array $missing) : array
    {
        $values = [];

        foreach ($missing as $missingKey) {
            $values[$missingKey['key']] = $this->ask(
                sprintf(
                    '%s translation: (Hint: %s)',
                    $missingKey['key'],
                    $missingKey['hint']
                ),
                ''
            );
        }

        return $values;
    }

    /**
     * Get an array of keys that have missing values with a hint
     * from another language translation file.
     *
     * ex: [ ['key' => 'product.color.nl', 'hint' => 'en = "color"'] ]
     *
     * @param array $languages
     * @return array
     */
    private function getMissing(array $languages) : array
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

        // Array of keys indexed by fileName.key, those keys we looked
        // at before so we save them in order for us to not look
        // at them again in a different language iteration.
        $searched = [];

        foreach (array_dot($filesResults) as $key => $value) {
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