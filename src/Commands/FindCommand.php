<?php

namespace Themsaid\Langman\Commands;

class FindCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:find {keyword}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Find key with values matching the keyword.';

    /**
     * Array of files grouped by filename.
     *
     * @var array
     */
    protected $files;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->files = $this->manager->files();

        if (empty($this->files)) {
            $this->warn('No language files were found!');
        }

        $languages = $this->manager->languages();

        $this->table(
            array_merge(['key'], $languages),
            $this->tableRows()
        );
    }

    /**
     * The output of the table rows.
     *
     * @return array
     */
    private function tableRows()
    {
        $allLanguages = $this->manager->languages();

        $filesContent = [];

        $output = [];

        foreach ($this->files as $fileName => $fileLanguages) {
            foreach ($fileLanguages as $languageKey => $filePath) {
                $lines = $filesContent[$fileName][$languageKey] = $this->manager->getFileContent($filePath);

                foreach ($lines as $key => $line) {
                    $key    = $fileName . '.' . $key;
                    $output = $this->searchLanguageFiles($output, $key, $fileName, $languageKey, $line);
                }
            }
        }

        // Now that we collected all values that matches the keyword argument
        // in a close match, we collect the values for the rest of the
        // languages for the found keys to complete the table view.
        foreach ($output as $fullKey => $values) {
            list($fileName, $key) = $this->getFileAndKey($fullKey);

            $original = [];

            foreach ($allLanguages as $languageKey) {
                $original[$languageKey] =
                    isset($values[$languageKey])
                        ? $values[$languageKey]
                        : array_get($filesContent, "$fileName.$languageKey.$key", '');
            }

            // Sort the language values based on language name
            ksort($original);

            $output[$fullKey] = array_merge(['key' => "<fg=yellow>$fullKey</>"], $original);
        }

        return array_values($output);
    }
}
