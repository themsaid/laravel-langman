<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Themsaid\Langman\Manager;

class MissingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:missing {--default}';

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
        $this->info('Looking for missing translations...');

        $languages = $this->manager->languages();

        $missing = $this->getMissing($languages);

        $values = $this->collectValues($missing);

        $input = [];

        foreach ($values as $key => $value) {
            preg_match('/^([^\.]*)\.(.*):(.*)/', $key, $matches);

            $input[$matches[1]][$matches[2]][$matches[3]] = $value;

            $this->line("\"<fg=yellow>{$key}</>\" was set to \"<fg=yellow>{$value}</>\" successfully.");
        }

        foreach ($input as $fileName => $values) {
            $this->manager->fillKeys(
                $fileName,
                $values
            );
        }

        $this->info('Done!');
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
            $key = $missingKey;
            if (substr($key, 0, 6) == "-json.") {
                $key = substr($key, 6);
            }
            $values[$missingKey] = $this->ask(
               "<fg=yellow>{$key}</> translation", $this->getDefaultValue($missingKey)
            );
        }

        return $values;
    }

    /**
     * Get translation in default locale for the given key.
     *
     * @param string $missingKey
     * @return string
     */
    private function getDefaultValue($missingKey)
    {
        if (! $this->option('default')) {
            return null;
        }

        try {
            $missingKey = explode(':', $missingKey)[0];

            list($file, $key) = explode('.', $missingKey);

            $filePath = $this->manager->files()[$file][config('app.locale')];

            return config('app.locale').":{$this->manager->getFileContent($filePath)[$key]}";
        } catch (\Exception $e) {
            return null;
        }
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

        $values = Arr::dot($filesResults);

        $emptyValues = array_filter($values, function ($value) {
            return $value == '';
        });

        // Adding all keys that has values = ''
        foreach ($emptyValues as $dottedValue => $emptyValue) {
            list($fileName, $languageKey, $key) = explode('.', $dottedValue, 3);

            $missing[] = "{$fileName}.{$key}:{$languageKey}";
        }

        $missing = array_merge($missing, $this->manager->getKeysExistingInALanguageButNotTheOther($values));

        return $missing;
    }
}
