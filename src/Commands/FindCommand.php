<?php

namespace Themsaid\LangMan\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Themsaid\LangMan\Manager;

class FindCommand extends Command
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
    private function tableRows() : array
    {
        $output = [];

        foreach ($this->files as $fileName => $languages) {
            foreach ($languages as $languageKey => $filePath) {
                $lines = (array) include $filePath;

                foreach ($lines as $key => $line) {
                    if (Str::contains($line, $this->argument('keyword'))) {
                        $output[$key]['key'] = $fileName.'.'.$key;
                        $output[$key][$languageKey] = $line;
                    }
                }
            }
        }

        return array_values($output);
    }
}