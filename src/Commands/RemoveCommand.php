<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Themsaid\Langman\Manager;

class RemoveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:remove {key}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Remove the given key from all language files.';

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
        try {
            list($file, $key) = explode('.', $this->argument('key'), 2);
        } catch (\ErrorException $e) {
            $this->error('Could not recognize the key you want to translate.');

            return;
        }

        if ($this->confirm("Are you sure you want to remove \"{$file}.{$key}\"?")) {
            $this->manager->removeKey($file, $key);

            $this->info("{$file}.{$key} was removed successfully.");
        }
    }
}
