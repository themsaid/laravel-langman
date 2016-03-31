<?php

namespace Themsaid\LangMan;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class Manager
{
    /**
     * @var Filesystem
     */
    private $disk;

    /**
     * @var string
     */
    private $path;

    /**
     * Manager constructor.
     *
     * @param Filesystem $disk
     * @param string $path
     */
    public function __construct(Filesystem $disk, string $path)
    {
        $this->disk = $disk;
        $this->path = $path;
    }

    /**
     * Array of language files grouped by file name.
     *
     * ex: ['user' => ['en' => 'user.php', 'nl' => 'user.php']]
     *
     * @return array
     */
    public function files(): array
    {
        $files = Collection::make($this->disk->allFiles($this->path));

        $filesByFile = $files->groupBy(function ($file) {
            return $file->getBasename('.'.$file->getExtension());
        })->map(function ($files) {
            return $files->keyBy(function ($file) {
                return str_replace($this->path.'/', '', $file->getPath());
            })->map(function ($file) {
                return $file->getRealPath();
            });
        });

        return $filesByFile->toArray();
    }

    /**
     * Array of supported languages.
     *
     * ex: ['en', 'sp']
     *
     * @return array
     */
    public function languages() : array
    {
        return array_map(function ($directory) {
            return str_replace($this->path.'/', '', $directory);
        }, $this->disk->directories($this->path));
    }
}