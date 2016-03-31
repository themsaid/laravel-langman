<?php

namespace Themsaid\Langman;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
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

    /**
     * Create a file for all languages if does not exist already.
     *
     * @param $fileName
     * @return void
     */
    public function createFile($fileName)
    {
        foreach ($this->languages() as $languageKey) {
            $file = $this->path."/{$languageKey}/{$fileName}.php";
            if (! $this->disk->exists($file)) {
                file_put_contents($file, "<?php \n");
            }
        }
    }

    /**
     * Fills a translation line for the given key.
     *
     * @param string $fileName
     * @param string $key
     * @param array $values
     * @return void
     */
    public function fillKey(string $fileName, string $key, array $values)
    {
        foreach ($values as $languageKey => $value) {
            try {
                $filePath = $this->path."/{$languageKey}/{$fileName}.php";

                $fileContent = (array) include $filePath;
            } catch (\ErrorException $e) {
                throw new FileNotFoundException('File not found: '.$filePath);
            }

            $fileContent[$key] = $value;

            $this->writeFile($filePath, $fileContent);
        }
    }

    /**
     * Write a language file from array.
     *
     * @param string $filePath
     * @param array $translations
     * @return void
     */
    public function writeFile(string $filePath, array $translations)
    {
        $content = "<?php \n\n return [";

        foreach ($translations as $key => $value) {
            $content .= "'{$key}' => '{$value}',";
        }

        $content .= "\n];";

        file_put_contents($filePath, $content);
    }
}