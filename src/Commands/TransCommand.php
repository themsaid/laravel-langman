<?php

namespace Themsaid\LangMan\Commands;

use Illuminate\Console\Command;
use Themsaid\LangMan\Manager;

class TransCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:trans {key}';

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

        if (empty($this->files = $this->filesFromKey())) {
            return;
        }

        $this->fillKey();
    }

    /**
     * Parse the given key argument.
     *
     * @return boolean
     */
    private function parseKey() : bool
    {
        try {
            list($this->fileName, $this->key, $this->languageKey) = explode('.', $this->argument('key'));
        } catch (\ErrorException $e) {
            if (! $this->key) {
                $this->error('Could not recognize the key you want to translate.');

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
    private function filesFromKey(): array
    {
        try {
            return $this->manager->files()[$this->fileName];
        } catch (\ErrorException $e) {
            if ($this->confirm(sprintf('Language file %s.php not found, would you like to create it?', $this->fileName))) {
                $this->manager->createFile($this->fileName);
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
        $values = [];

        foreach ($this->manager->languages() as $languageKey) {
            $languageContent = (array) include $this->files[$languageKey];

            $values[$languageKey] = $this->ask(
                sprintf(
                    '%s.%s.%s translation%s:',
                    $this->fileName,
                    $this->key,
                    $languageKey,
                    isset($languageContent[$this->key]) ? ' (updating)' : ''
                ),
                $languageContent[$this->key] ?? ''
            );
        }

        $this->manager->fillKey($this->fileName, $this->key, $values);
    }
}