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
    protected $signature = 'langman:show {key?} {--c|close} {--lang=} {--u|unused}';

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

        $excluded = null;
        if ($this->option('unused')) {
            $allKeysInFiles = $this->manager->collectFromFiles();
            $keyfile = $this->file ?: "-json";
            if (isset($allKeysInFiles[$keyfile])) {
                $excluded = array_values($allKeysInFiles[$keyfile]);
            }
        }

        try {
            $this->languages = $this->getLanguages();
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $displayFile = $this->file ?: "JSON strings";
        $closematch = $this->key === null ? "" : ($this->option('close') ? 'using substring match' : 'using equality match');
        $unused = $this->option('unused') ? 'unused keys' : 'keys';
        $what = $this->key === null ? "all $unused" : "specific $unused matching '$this->key'";
        $this->info("Displaying $what from $displayFile $closematch");

        $this->table(
            array_merge(['key'], $this->languages),
            $this->tableRows($excluded)
        );
    }

    /**
     * The output of the table rows.
     *
     * @param $allKeysInFiles Array? if set, a list of all keys we should filter out
     * @return array
     */
    private function tableRows($excluded)
    {
        $output = [];

        $filesContent = [];

        foreach ($this->files as $languageKey => $file) {
            foreach ($filesContent[$languageKey] = Arr::dot($this->manager->getFileContent($file)) as $key => $value) {
                if (! $this->shouldShowKey($key, $excluded)) {
                    continue;
                }

                $okey = strlen($key) > 40 ? substr($key, 0, 36)." ..." : $key;
                $output[$key]['key'] = $okey;
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

            $okey = strlen($key) > 40 ? substr($key, 0, 36)." ..." : $key;
            $output[$key] = array_merge(['key' => "<fg=yellow>$okey</>"], $original);
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
            return $this->manager->files()[$this->file ?? "-json"];
        } catch (\ErrorException $e) {
            if ($this->file === null) {
                $this->error(sprintf('JSON language strings not found!', $this->file));
            } else {
                $this->error(sprintf('Language file %s.php not found!', $this->file));
            }

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
        if (strlen($this->argument('key'))) {
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
            } else {
                if ($this->key === null && !isset($this->manager->files()[$this->file])) {
                    // fallback on a key search in the JSON strings
                    $this->key = $this->file;
                    $this->file = null;
                }
            }

            // if we want to use --close on the JSON strings, the key is _always_ a key, even
            // if it is also a file
            if ($this->key === null && $this->option('close')) {
                $this->key = $this->file;
                $this->file = null;
            }
        } else {
            $this->file = null;
            $this->key = null;
        }
    }

    /**
     * Determine if the given key should exist in the output.
     *
     * @param $key
     *
     * @return bool
     */
    private function shouldShowKey($key, $exclude)
    {
        if ($exclude != null && in_array($key, $exclude)) {
            return false;
        }

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
