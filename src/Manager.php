<?php

namespace Themsaid\Langman;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
    public function __construct(Filesystem $disk, $path, array $viewsPaths)
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
    public function files()
    {
        $files = Collection::make($this->disk->allFiles($this->path));

        $filesByFile = $files->groupBy(function ($file) {
            $fileName = $file->getBasename('.'.$file->getExtension());

            if (Str::contains($file->getPath(), 'vendor')) {
                preg_match('/([^\/]*)\/([^\/]*)\/([^\/]*).php$/', $file->getRealPath(), $matches);

                return "{$matches[1]}::{$matches[3]}";
            } else {
                return $fileName;
            }
        })->map(function ($files) {
            return $files->keyBy(function ($file) {
                return basename($file->getPath());
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
    public function languages()
    {
        $languages = array_map(function ($directory) {
            return basename($directory);
        }, $this->disk->directories($this->path));

        sort($languages);

        return $languages;
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
                file_put_contents($file, "<?php \n\n return[];");
            }
        }
    }

    /**
     * Fills translation lines for given keys in different languages.
     *
     * ex. for $keys = ['name' => ['en' => 'name', 'nl' => 'naam']
     *
     * @param string $fileName
     * @param array $keys
     * @return void
     */
    public function fillKeys($fileName, array $keys)
    {
        $appends = [];

        foreach ($keys as $key => $values) {
            foreach ($values as $languageKey => $value) {
                $filePath = $this->path."/{$languageKey}/{$fileName}.php";

                Arr::set($appends[$filePath], $key, $value);
            }
        }

        foreach ($appends as $filePath => $values) {
            $fileContent = $this->getFileContent($filePath, true);

            $newContent = array_replace_recursive($fileContent, $values);

            array_walk_recursive($newContent, function ($value) {
                return addslashes($value);
            });

            $this->writeFile($filePath, $newContent);
        }
    }

    /**
     * Remove a key from all language files.
     *
     * @param string $fileName
     * @param string $key
     * @return void
     */
    public function removeKey($fileName, $key)
    {
        foreach ($this->languages() as $language) {
            $filePath = $this->path."/{$language}/{$fileName}.php";

            $fileContent = $this->getFileContent($filePath);

            Arr::forget($fileContent, $key);

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
    public function writeFile($filePath, array $translations)
    {
        $content = "<?php \n\nreturn [";

        $content .= $this->stringLineMaker($translations);

        $content .= "\n];";

        file_put_contents($filePath, $content);
    }

    /**
     * Write the lines of the inner array of the language file.
     *
     * @param $array
     * @return string
     */
    private function stringLineMaker($array, $prepend = '')
    {
        $output = '';

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->stringLineMaker($value, $prepend.'    ');
                $output .= "\n{$prepend}    '{$key}' => [{$value}\n{$prepend}    ],";
            } else {
                $output .= "\n{$prepend}    '{$key}' => '{$value}',";
            }
        }

        return $output;
    }

    /**
     * Get the content in the given file path.
     *
     * @param string $filePath
     * @param bool $createIfNotExists
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getFileContent($filePath, $createIfNotExists = false)
    {
        if ($createIfNotExists && ! $this->disk->exists($filePath)) {
            if (! $this->disk->exists($directory = dirname($filePath))) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($filePath, "<?php\n\nreturn [];");

            return [];
        }

        try {
            return (array) include $filePath;
        } catch (\ErrorException $e) {
            throw new FileNotFoundException('File not found: '.$filePath);
        }
    }

    /**
     * Collect all translation keys from views files.
     *
     * @return array
     */
    public function collectFromViews()
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
            '('.implode('|', $functions).')'.// Must start with one of the functions
            "\(".// Match opening parentheses
            "[\'\"]".// Match " or '
            '('.// Start a new group to match:
            '[a-zA-Z0-9_-]+'.// Must start with group
            "([.][^\1)]+)+".// Be followed by one or more items/keys
            ')'.// Close group
            "[\'\"]".// Closing quote
            "[\),]"  // Close parentheses or new parameter
        ;

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->disk->allFiles($this->viewsPaths) as $file) {
            if (preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
                foreach ($matches[2] as $key) {
                    try {
                        list($fileName, $keyName) = explode('.', $key, 2);
                    } catch (\ErrorException $e) {
                        continue;
                    }

                    if (isset($output[$fileName]) && in_array($keyName, $output[$fileName])) {
                        continue;
                    }

                    $output[$fileName][] = $keyName;
                }
            }
        }

        return $output;
    }
}
