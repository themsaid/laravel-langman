<?php

namespace Themsaid\Langman;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class Manager
{
    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    private $disk;

    /**
     * The path to the language files.
     *
     * @var string
     */
    private $path;

    /**
     * the paths to the views files.
     *
     * @var array
     */
    private $viewsPaths;

    /**
     * Manager constructor.
     *
     * @param Filesystem $disk
     * @param string $path
     */
    public function __construct(Filesystem $disk, string $path, array $viewsPaths)
    {
        $this->disk = $disk;
        $this->path = $path;
        $this->viewsPaths = $viewsPaths;
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
            $filePath = $this->path."/{$languageKey}/{$fileName}.php";

            $fileContent = $this->getFileContent($filePath);

            $fileContent[$key] = $value;

            $this->writeFile($filePath, $fileContent);
        }
    }

    /**
     * Remove a key from all language files.
     *
     * @param string $fileName
     * @param string $key
     * @return void
     */
    public function removeKey(string $fileName, string $key)
    {
        foreach ($this->languages() as $language) {
            $filePath = $this->path."/{$language}/{$fileName}.php";

            $fileContent = $this->getFileContent($filePath);

            unset($fileContent[$key]);

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
        $content = "<?php \n\nreturn [";

        foreach ($translations as $key => $value) {
            $content .= "\n    '{$key}' => '{$value}',";
        }

        $content .= "\n];";

        file_put_contents($filePath, $content);
    }

    /**
     * Get the content in the given file path.
     *
     * @param $filePath
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getFileContent($filePath, $createIfNotExists = false) : array
    {
        try {
            return (array) include $filePath;
        } catch (\ErrorException $e) {
            if ($createIfNotExists) {
                if (! $this->disk->exists($directory = $this->disk->dirname($filePath))) {
                    mkdir($directory, true);
                }

                file_put_contents($filePath, "<?php\n\nreturn [];");

                return [];
            }

            throw new FileNotFoundException('File not found: '.$filePath);
        }
    }

    /**
     * Collect all translation keys from views files.
     *
     * @return array
     */
    public function collectFromViews() : array
    {
        $output = [];

        /*
         * This pattern is derived from Barryvdh\TranslationManager by Barry vd. Heuvel <barryvdh@gmail.com>
         *
         * https://github.com/barryvdh/laravel-translation-manager/blob/master/src/Manager.php
         */
        $functions = ['trans', 'trans_choice', 'Lang::get', 'Lang::choice', 'Lang::trans', 'Lang::transChoice', '@lang', '@choice'];

        $pattern =
            // See http://regexr.com/392hu
            "(".implode('|', $functions).")". // Must start with one of the functions
            "\(". // Match opening parentheses
            "[\'\"]". // Match " or '
            "(". // Start a new group to match:
            "[a-zA-Z0-9_-]+". // Must start with group
            "([.][^\1)]+)+". // Be followed by one or more items/keys
            ")". // Close group
            "[\'\"]". // Closing quote
            "[\),]"  // Close parentheses or new parameter
        ;

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->disk->allFiles($this->viewsPaths) as $file) {
            if (preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
                foreach ($matches[2] as $key) {
                    try {
                        list($fileName, $keyName) = explode('.', $key);
                    } catch (\ErrorException $e) {
                        continue;
                    }
                    $output[$fileName][] = $keyName;
                }
            }
        }

        return $output;
    }
}