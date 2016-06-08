<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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
        if ($this->areArgumentsValid ()){
            list( $file, $key ) = explode ( '.',$this->argument('key'), 2 );

            $files = $this->manager->files ()[$file];

            $this->changeKeyNameInAllLanguageFiles ( $files, $key );

            $this->generateReportForViewFilesAffected ( );

            $this->info ( "Done!" );
        }
    }

    /**
     * Check if both arguments are properly formatted.
     *
     * @return bool
     */
    protected function areArgumentsValid ()
    {
        $areValid = true;

        if ( $this->isKeyAnArgumentValid () ){
            $this->error ( "Invalid <key> argument format! Pls check and try again." );
            $areValid = false;
        }

        if ( $this->isAsAnArgumentValid () ){
            $this->error ( "Invalid <as> argument format! Pls check and try again." );
            $areValid = false;
        }

        return $areValid;
    }

    /**
     * Change 
     *
     * @param $file
     * @param $key
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function changeKeyName ( $file, $key )
    {
        $content = $this->manager->getFileContent ( $file );

        $oldKeyValue = array_pull ( $content, $key );

        $newKey = preg_replace ( '/(\w+)$/i', $this->argument ( 'as' ), $key );

        array_set ( $content, $newKey, $oldKeyValue );

        $this->manager->writeFile ( $file, $content );
    }

    /**
     * @param $files
     * @param $key
     * @return mixed
     */
    private function changeKeyNameInAllLanguageFiles ( $files, $key )
    {
        foreach ( $files as $file ) {
            $this->changeKeyName ( $file, $key );
        }
    }

    /**
     * @param $affected
     */
    private function generateReportForViewFilesAffected ( )
    {
        if ( $affected = $this->getOnlyViewFilesAffected () ){
            $rows = $this->generateReportRows ( $affected );
            $this->info ( count ( $affected ) . ' views files has been affected.' );
            $this->table ( [ 'Times', 'View File' ], $rows );
        }
    }

    /**
     * @return array
     */
    private function getOnlyViewFilesAffected ()
    {
        $affected = [ ];

        foreach ( $this->manager->getAllViewFilesWithTranslations () as $file => $references ) {
            foreach ( $references as $reference ) {
                if ( $reference == $this->argument ( 'key' ) ) {
                    $affected[ $file ][] = $reference;
                }
            }
        }
        return $affected;
    }

    /**
     * @param $affected
     * @param $report
     * @return array
     */
    private function generateReportRows ( $affected )
    {
        $rows = [];
        foreach ( $affected as $file => $keys ) {
            $rows[] = [ count ( $keys ), $file ];
        }
        return $rows;
    }

    /**
     * @return bool
     */
    protected function isKeyAnArgumentValid ()
    {
        return !Str::contains ( $this->argument ( 'key' ), '.' ) || is_null ( $this->argument ( 'key' ) );
    }

    /**
     * @return bool
     */
    protected function isAsAnArgumentValid ()
    {
        return Str::contains ( $this->argument ( 'as' ), '.' ) || is_null ( $this->argument ( 'as' ) );
    }
}
