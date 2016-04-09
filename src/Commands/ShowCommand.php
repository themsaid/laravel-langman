<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Themsaid\Langman\Manager;

class ShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:show {key} {--c|close}';

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

        $this->table(
            array_merge(['key'], $this->manager->languages()),
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

        $output = [];

        $filesContent = [];

        foreach ($this->files as $languageKey => $file) {
            foreach ($filesContent[$languageKey] = Arr::dot($this->manager->getFileContent($file)) as $key => $value) {
                if (! $this->shouldShowKey($key)) {
                    continue;
                }

                $output[$key]['key'] = $key;
                $output[$key][$languageKey] = $value;
            }
        }

        // Now that we have collected all existing values, we are going to fill the
        // missing ones with emptiness indicators to balance the table structure
        // and alert developers so that they can take proper actions.
        foreach ($output as $key => $values) {
            $original = [];

            foreach ($allLanguages as $languageKey) {
                $original[$languageKey] = isset($values[$languageKey]) ? $values[$languageKey] : '<bg=red>  MISSING  </>';
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
}
