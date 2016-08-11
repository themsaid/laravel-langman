<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Themsaid\Langman\Manager;

class RenameCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:rename {oldKey} {newKey}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Rename the given key.';

    /**
     * The Languages manager instance.
     *
     * @var \Themsaid\LangMan\Manager
     */
    private $manager;

    /**
     * Array of files grouped by filename.
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
        $this->renameKey();

        $this->listFilesContainingOldKey();

        $this->info('The key at '.$this->argument('oldKey').' was renamed to '.$this->argument('newKey').' successfully!');
    }

    /**
     * Rename the given oldKey to the newKey.
     *
     * @return void
     */
    private function renameKey()
    {
        try {
            list($file, $key) = explode('.', $this->argument('oldKey'), 2);
        } catch (\ErrorException $e) {
            $this->error('Could not recognize the key you want to rename.');

            return;
        }

        if (Str::contains($this->argument('newKey'), '.')) {
            $this->error('Please provide the new key must not contain a dot.');

            return;
        }

        $newKey = preg_replace('/(\w+)$/i', $this->argument('newKey'), $key);

        $files = $this->manager->files()[$file];

        $currentValues = [];

        foreach ($files as $languageKey => $filePath) {
            $content = Arr::dot($this->manager->getFileContent($filePath));

            $currentValues[$languageKey] = isset($content[$key]) ? $content[$key] : '';
        }

        $this->manager->removeKey($file, $key);

        $this->manager->fillKeys(
            $file,
            [$newKey => $currentValues]
        );
    }

    /**
     * Show a table with application files containing the old key.
     *
     * @return void
     */
    private function listFilesContainingOldKey()
    {
        if ($files = $this->getFilesContainingOldKey()) {
            $this->info('Renamed key was found in '.count($files).' file(s).');

            $this->table(['Encounters', 'File'], $this->getTableRows($files));
        }
    }

    /**
     * Get an array of application files containing the old key.
     *
     * @return array
     */
    private function getFilesContainingOldKey()
    {
        $affectedFiles = [];

        foreach ($this->manager->getAllViewFilesWithTranslations() as $file => $keys) {
            foreach ($keys as $key) {
                if ($key == $this->argument('oldKey')) {
                    $affectedFiles[$file][] = $key;
                }
            }
        }

        return $affectedFiles;
    }

    /**
     * Get table rows for the list of files containing the old key.
     *
     * @param array $files
     * @return array
     */
    private function getTableRows($files)
    {
        $rows = [];

        foreach ($files as $file => $keys) {
            $rows[] = [count($keys), $file];
        }

        return $rows;
    }
}
