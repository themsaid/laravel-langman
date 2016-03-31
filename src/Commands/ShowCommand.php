<?php

namespace Themsaid\LangMan\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Themsaid\LangMan\Manager;

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

        $this->files = $this->filesForKey();

        $this->table(
            array_merge(['key'], array_keys($this->files)),
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
            $content = (array) include $file;

            foreach ($content as $key => $value) {
                if ($this->key) {
                    if ($this->option('c') && ! Str::contains($key, $this->key)) {
                        continue;
                    }
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
    private function filesForKey(): array
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
}