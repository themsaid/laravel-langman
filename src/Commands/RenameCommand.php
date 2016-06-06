<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Themsaid\Langman\Manager;

class RenameCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:rename {key} {as}';

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
        list( $file, $key ) = explode ( '.',$this->argument('key'), 2 );

        $files = $this->manager->files ()[$file];

        foreach ( $files as $file ) {
            $content = $this->manager->getFileContent ( $file );

            $oldKeyValue = array_pull ( $content, $key );

            $newKey = preg_replace('/(\w+)$/i', $this->argument('as'), $key);

            array_set ( $content, $newKey, $oldKeyValue );

            $this->manager->writeFile ($file , $content );
        }

        $affected = [];

        foreach ($this->manager->getAllViewFilesWithTranslations() as $file => $references) {
            foreach ($references as $reference) {
                if ($reference == $this->argument('key')) {
                    $affected[$file][] = $reference;
                }
            }
        }

        $report = [];

        foreach ($affected as $file => $keys) {
            $report[] = [count($keys), $file];
        }

        $this->info("Views Files Affected");
        $this->table(['Times', 'View File'], $report);
    }
}
