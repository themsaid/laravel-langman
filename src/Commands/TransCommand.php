<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Themsaid\Langman\Manager;

class TransCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:trans {key} {--lang=}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Add or update translations for the given key.';

    /**
     * The name of the language file we're going to alter.
     *
     * @var string
     */
    protected $fileName;

    /**
     * The name of the package.
     *
     * @var string
     */
    protected $packageName;

    /**
     * The name of the only language we're going to alter its file.
     *
     * @var null|string
     */
    protected $languageKey;

    /**
     * The name of the key we're going to update or create.
     *
     * @var string
     */
    protected $key;

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
        if (! $this->parseKey()) {
            return;
        }

        $this->languageKey = $this->option('lang');

        if (empty($this->files = $this->filesFromKey())) {
            return;
        }

        $this->fillKey();
    }

    /**
     * Parse the given key argument.
     *
     * @return bool
     */
    private function parseKey()
    {
        try {
            preg_match('/^([^\.]*)\.([^\:]*)/', $this->argument('key'), $matches);

            $this->fileName = $matches[1];
            $this->key = $matches[2];
        } catch (\ErrorException $e) {
            if (! $this->key) {
                $this->error('Could not recognize the key you want to translate.');

                return false;
            }
        }

        if (Str::contains($this->fileName, '::')) {
            try {
                $parts = explode('::', $this->fileName);

                $this->manager->setPathToVendorPackage($parts[0]);

                $this->packageName = $parts[0];
            } catch (\ErrorException $e) {
                $this->error('Could not recognize the package.');

                return false;
            }
        }

        return true;
    }

    /**
     * Array of requested file in different languages.
     *
     * @return array
     */
    private function filesFromKey()
    {
        try {
            return $this->manager->files()[$this->fileName];
        } catch (\ErrorException $e) {
            if ($this->confirm(sprintf('Language file %s.php not found, would you like to create it?', $this->fileName))) {
                $this->manager->createFile(str_replace($this->packageName.'::', '', $this->fileName));
            }

            return [];
        }
    }

    /**
     * Fill a translation key in all languages.
     *
     * @return void
     */
    private function fillKey()
    {
        $languages = $this->manager->languages();

        if ($this->languageKey) {
            if (! in_array($this->languageKey, $languages)) {
                $this->error(sprintf('Language (%s) could not be found!', $this->languageKey));

                return;
            }

            // If a language key was specified then we prompt for it only.
            $languages = [$this->languageKey];
        }

        $values = $this->collectValues($languages);

        $this->manager->fillKeys(
            str_replace($this->packageName.'::', '', $this->fileName),
            [$this->key => $values]
        );

        foreach ($values as $languageKey => $value) {
            $this->line("<fg=yellow>{$this->fileName}.{$this->key}:{$languageKey}</> was set to \"<fg=yellow>{$value}</>\" successfully.");
        }
    }

    /**
     * Collect translation values from console via questions.
     *
     * @param $languages
     * @return array
     */
    private function collectValues($languages)
    {
        $values = [];

        foreach ($languages as $languageKey) {
            $languageContent = $this->manager->getFileContent($this->files[$languageKey]);

            $values[$languageKey] = $this->ask(
                sprintf(
                    '<fg=yellow>%s.%s:%s</> translation',
                    $this->fileName,
                    $this->key,
                    $languageKey
                ),
                isset($languageContent[$this->key]) ? $languageContent[$this->key] : null
            );
        }

        return $values;
    }
}
