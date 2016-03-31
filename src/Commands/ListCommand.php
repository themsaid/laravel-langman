<?php

namespace Themsaid\LangMan\Commands;

use Illuminate\Console\Command;
use Themsaid\LangMan\Manager;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:list {file}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'List language lines for a given file.';

    /**
     * The Languages manager instance.
     *
     * @var \Themsaid\LangMan\Manager
     */
    private $manager;

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
        try {
            $files = $this->manager->files()[$this->argument('file')];
        } catch (\ErrorException $e) {
            $this->error(sprintf('Language file %s.php not found!', $this->argument('file')));

            return;
        }

        $this->table(
            array_merge(['key'], array_keys($files)),
            $this->tableRows($files)
        );
    }

    /**
     * The output of the table rows.
     *
     * @param array $files
     * @return array
     */
    public function tableRows($files) : array
    {
        $output = [];

        foreach ($files as $language => $file) {
            $content = (array) include $file;

            foreach ($content as $key => $value) {
                $output[$key]['key'] = $key;
                $output[$key][$language] = $value;
            }
        }

        return array_values($output);
    }
}