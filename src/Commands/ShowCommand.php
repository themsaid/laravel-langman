<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Themsaid\Langman\Manager;

class ShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:show {key} {--c|close} {--lang=}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Show language lines for a given file or key.';

    /**
     * The Languages manager instance.
     *
     * @var \Themsaid\LangMan\Manager
     */
    private $manager;

    /**
     * Filename to read from.
     *
     * @var string
     */
    protected $file;

    /**
     * Key name to show results for.
     *
     * @var string
     */
    protected $key;

    /**
     * Array of requested file in different languages.
     *
     * @var array
     */
    protected $files;

    /**
     * Array of displayable languages.
     *
     * @var array
     */
    protected $languages;

    /**
     * ListCommand constructor.
     *
     * @param \Themsaid\LangMan\Manager $manager
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
        $this->parseKey();

        $this->files = $this->filesFromKey();

        try {
            $this->languages = $this->getLanguages();
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->table(
            array_merge(['key'], $this->languages),
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
        $output = [];

        $filesContent = [];

        foreach ($this->files as $languageKey => $file) {
            foreach ($filesContent[$languageKey] = Arr::dot($this->manager->getFileContent($file)) as $key => $value) {
                if (! $this->shouldShowKey($key)) {
                    continue;
                }

                $output[$key]['key'] = $key;
                $output[$key][$languageKey] = $value ?: '';
            }
        }

        // Now that we have collected all existing values, we are going to fill the
        // missing ones with emptiness indicators to balance the table structure
        // and alert developers so that they can take proper actions.
        foreach ($output as $key => $values) {
            $original = [];

            foreach ($this->languages as $languageKey) {
                $original[$languageKey] = isset($values[$languageKey]) && $values[$languageKey]
                    ? $values[$languageKey]
                    : '<bg=red>  MISSING  </>';
            }

            // Sort the language values based on language name
            ksort($original);

            $output[$key] = array_merge(['key' => "<fg=yellow>$key</>"], $original);
        }

        return array_values($output);
    }

    /**
     * Array of requested file in different languages.
     *
     * @return array
     */
    private function filesFromKey()
    {
        try {
            return $this->manager->files()[$this->file];
        } catch (\ErrorException $e) {
            $this->error(sprintf('Language file %s.php not found!', $this->file));

            return [];
        }
    }

    /**
     * Parse the given key argument.
     *
     * @return void
     */
    private function parseKey()
    {
        $parts = explode('.', $this->argument('key'), 2);

        $this->file = $parts[0];

        $this->key = isset($parts[1]) ? $parts[1] : null;

        if (Str::contains($this->file, '::')) {
            try {
                $parts = explode('::', $this->file);

                $this->manager->setPathToVendorPackage($parts[0]);
            } catch (\ErrorException $e) {
                $this->error('Could not recognize the package.');

                return;
            }
        }
    }

    /**
     * Determine if the given key should exist in the output.
     *
     * @param $key
     *
     * @return bool
     */
    private function shouldShowKey($key)
    {
        if ($this->key) {
            if (Str::contains($key, '.') && Str::startsWith($key, $this->key)) {
                return true;
            }

            if (! $this->option('close') && $key != $this->key) {
                return false;
            }

            if ($this->option('close') && ! Str::contains($key, $this->key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the languages to be displayed in the command output.
     *
     * @return array
     */
    private function getLanguages()
    {
        $allLanguages = $this->manager->languages();

        if (! $this->option('lang')) {
            return $allLanguages;
        }

        $userLanguages = explode(',', (string) $this->option('lang'));

        if ($missingLanguages = array_diff($userLanguages, $allLanguages)) {
            throw new InvalidArgumentException('Unknown Language(s) ['.implode(',', $missingLanguages).'].');
        }

        return $userLanguages;
    }
}
