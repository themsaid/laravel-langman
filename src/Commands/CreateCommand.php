<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command;
use Themsaid\Langman\Manager;
use Exception;

class CreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:create {path}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Create unlimited nested files.';

    /**
     * The name of the directory that we will create.
     *
     * @var string
     */
    protected $directory;

    /**
     * The full files path that we well create.
     *
     * @var string
     */
    protected $filesPath;

    /**
     * The Languages manager instance.
     *
     * @var \Themsaid\LangMan\Manager
     */
    private $manager;

    /**
     * The Config instance.
     *
     * @var Config
     */
    private $config;

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
        $directories = $this->parsePath();

        $this->createDirectories($directories);

        $this->createLanguagesFile();
    }

    /**
     * Parse the given key argument.
     *
     * @return bool
     */
    private function parsePath()
    {
    	$this->filesPath = $this->argument('path');
    	
    	// list of directories with full path to create;
    	$directories = [];
    	
    	// concatinate paths
    	$last_path = '';
    	
    	// list paths
    	$paths = explode('.',$this->filesPath);
    	
    	// generate directories paths
    	foreach($paths as $index => $path){
    		$directories[] = $last_path.$path.'/';
    		$last_path = $directories[$index];
    	}

    	// set the file path
    	$this->filesPath = $last_path;

    	// return directories list 
    	return $directories;
    }

    /**
     * Create Directories
     *
     * @return void
     */
    private function createDirectories(array $directories)
    {
    	$lang_path = $this->manager->getPath();
    	foreach($directories as $directory) {
			if (! is_dir($lang_path.'/'.$directory)) {
				mkdir($lang_path.'/'.$directory, 0755, true);
			}
		}
        $this->directory = rtrim($directory,'/');
    }

    /**
     * Create a file for all languages if does not exist already.
     *
     * @param $fileName
     * @return void
     */
    public function createLanguagesFile()
    {
    	$path = $this->manager->getPath().'/'.$this->filesPath;
        $languages = array_diff($this->manager->languages(),[$this->directory]);
        foreach ($languages as $languageKey) {
            if (! is_dir($path.$languageKey)) {
				mkdir($path.$languageKey, 0755, true);
			}
    	}
	}
}
