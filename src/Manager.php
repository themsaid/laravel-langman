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
     * The paths to directories where we look for localised strings to sync.
     *
     * @var array
     */
    private $syncPaths;

    /**
     * Manager constructor.
     *
     * @param Filesystem $disk
     * @param string $path
     */
    public function __construct(Filesystem $disk, $path, array $syncPaths)
    {
        $this->disk = $disk;
        $this->path = $path;
        $this->syncPaths = $syncPaths;
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
        $files = Collection::make($this->disk->allFiles($this->path))->filter(function ($file) {
            return $this->disk->extension($file) == 'php';
        });

        $filesByFile = $files->groupBy(function ($file) {
            $fileName = $file->getBasename('.'.$file->getExtension());

            if (Str::contains($file->getPath(), 'vendor')) {
                $fileName = str_replace('.php', '', $file->getFileName());

                $packageName = basename(dirname($file->getPath()));

                return "{$packageName}::{$fileName}";
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

        // If the path does not contain "vendor" then we're looking at the
        // main language files of the application, in this case we will
        // neglect all vendor files.
        if (! Str::contains($this->path, 'vendor')) {
            $filesByFile = $this->neglectVendorFiles($filesByFile);
        }

        return $filesByFile;
    }

    /**
     * Nelgect all vendor files.
     *
     * @param $filesByFile Collection
     * @return array
     */
    private function neglectVendorFiles($filesByFile)
    {
        $return = [];

        foreach ($filesByFile->toArray() as $key => $value) {
            if (! Str::contains($key, ':')) {
                $return[$key] = $value;
            }
        }

        return $return;
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

        $languages = array_filter($languages, function ($directory) {
            return $directory != 'vendor' && $directory != 'json';
        });

        sort($languages);

        return Arr::except($languages, ['vendor', 'json']);
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
                $value = str_replace('\"', '"', addslashes($value));

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
     * e.g. ['users' => ['city', 'name', 'phone']]
     *
     * @return array
     */
    public function collectFromFiles()
    {
        $translationKeys = [];

        foreach ($this->getAllViewFilesWithTranslations() as $file => $matches) {
            foreach ($matches as $key) {
                try {
                    list($fileName, $keyName) = explode('.', $key, 2);
                } catch (\ErrorException $e) {
                    continue;
                }

                if (isset($translationKeys[$fileName]) && in_array($keyName, $translationKeys[$fileName])) {
                    continue;
                }

                $translationKeys[$fileName][] = $keyName;
            }
        }

        return $translationKeys;
    }

    /**
     * Get found translation lines found per file.
     *
     * e.g. ['users.blade.php' => ['users.name'], 'users/index.blade.php' => ['users.phone', 'users.city']]
     *
     * @return array
     */
    public function getAllViewFilesWithTranslations()
    {
        /*
         * This pattern is derived from Barryvdh\TranslationManager by Barry vd. Heuvel <barryvdh@gmail.com>
         *
         * https://github.com/barryvdh/laravel-translation-manager/blob/master/src/Manager.php
         */
        $functions = ['__', 'trans', 'trans_choice', 'Lang::get', 'Lang::choice', 'Lang::trans', 'Lang::transChoice', '@lang', '@choice'];

        $pattern =
            // See https://regex101.com/r/jS5fX0/4
            '[^\w]'. // Must not start with any alphanum or _
            '(?<!->)'. // Must not start with ->
            '('.implode('|', $functions).')'.// Must start with one of the functions
            "\(".// Match opening parentheses
            "[\'\"]".// Match " or '
            '('.// Start a new group to match:
            '[a-zA-Z0-9_-]+'.// Must start with group
            "([.][^\1)$]+)+".// Be followed by one or more items/keys
            ')'.// Close group
            "[\'\"]".// Closing quote
            "[\),]"  // Close parentheses or new parameter
        ;

        $allMatches = [];

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->disk->allFiles($this->syncPaths) as $file) {
            if (preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
                $allMatches[$file->getRelativePathname()] = $matches[2];
            }
        }

        return $allMatches;
    }

    /**
     * Sets the path to a vendor package translation files.
     *
     * @param string $packageName
     * @return void
     */
    public function setPathToVendorPackage($packageName)
    {
        $this->path = $this->path.'/vendor/'.$packageName;
    }

    /**
     * Extract keys that exists in a language but not the other.
     *
     * Given a dot array of all keys in the format 'file.language.key', this
     * method searches for keys that exist in one language but not the
     * other and outputs an array consists of those keys.
     *
     * @param $values
     * @return array
     */
    public function getKeysExistingInALanguageButNotTheOther($values)
    {
        $missing = [];

        // Array of keys indexed by fileName.key, those are the keys we looked
        // at before so we save them in order for us to not look at them
        // again in a different language iteration.
        $searched = [];

        // Now we add keys that exist in a language but missing in any of the
        // other languages. Those keys combined with ones with values = ''
        // will be sent to the console user to fill and save in disk.
        foreach ($values as $key => $value) {
            list($fileName, $languageKey, $key) = explode('.', $key, 3);

            if (in_array("{$fileName}.{$key}", $searched)) {
                continue;
            }

            foreach ($this->languages() as $languageName) {
                if (! Arr::has($values, "{$fileName}.{$languageName}.{$key}") && ! array_key_exists("{$fileName}.{$languageName}.{$key}", $values)) {
                    $missing[] = "{$fileName}.{$key}:{$languageName}";
                }
            }

            $searched[] = "{$fileName}.{$key}";
        }

        return $missing;
    }
}
