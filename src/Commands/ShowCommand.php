<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Themsaid\Langman\Manager;
use Illuminate\Support\Str;

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
    private function tableRows() : array
    {
        $output = [];

        foreach ($this->files as $language => $file) {
            foreach ((array) include $file as $key => $value) {
                if (! $this->shouldShowKey($key)) {
                    continue;
                }

                $output[$key]['key'] = $key;
                $output[$key][$language] = $value;
            }
        }

        return array_values($output);
    }

    /**
     * Array of requested file in different languages.
     *
     * @return array
     */
    private function filesFromKey(): array
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
        try {
            list($this->file, $this->key) = explode('.', $this->argument('key'));
        } catch (\ErrorException $e) {
            // If explosion resulted 1 array item then it's the file, we
            // leave the key as null.
        }
    }

    /**
     * Determine if the given key should exist in the output.
     *
     * @param $key
     * @return bool
     */
    private function shouldShowKey($key) : bool
    {
        if ($this->key) {
            if ($this->option('close') && ! Str::contains($key, $this->key)) {
                return false;
            } elseif ($key != $this->key) {
                return false;
            }
        }

        return true;
    }
}