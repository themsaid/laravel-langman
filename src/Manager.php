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
     * The extension to the language files.
     *
     * @var string
     */
    private $extension;
    /**
     * The extension to the language files.
     *
     * @var array
     */
    private $exclude_extensions;
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
        $this->extension = 'php';
        $this->exclude_extensions = ['json',];
    }
    /**
     * Manager setExtension.
     *
     * @param string $extension
     */
    public function setExtension($extension){
        $this->extension = $extension;
    }
    /**
     * Manager setExcludeExtensions.
     *
     * @param array $exclude_extensions
     */
    public function setExcludeExtensions($exclude_extensions){
        $this->exclude_extensions = $exclude_extensions;
    }
    /**
     * get all the json file content
     *
     *
     * @return array
     */
    public function getJsonFilesContent($filesByFile,$lang)
    {
        
        $data = [];
        foreach ($filesByFile as $fileName => $value) {
            if (array_key_exists($lang,$filesByFile[$fileName])) // check is language avl 
            {
                $contents = $this->disk->get($filesByFile[$fileName][$lang]); // in get method provide full path of json file 
                $data[$fileName][$lang] = json_decode($contents, true);       // data['example_file']['en'] = array of json file content
            }
        }
        return $data;
        
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
            return $this->disk->extension($file) == $this->extension;
        });

        $filesByFile = $files->groupBy(function ($file) {
            $fileName = $file->getBasename('.'.$file->getExtension());

            if (Str::contains($file->getPath(), 'vendor')) {
                $fileName = str_replace('.'.$this->extension, '', $file->getFileName());

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
     * Include only specified names of files.
     *
     * @param array $filesByFile
     * @param array $includedFileNames 
     * @return array
     */
    public function filterByIncludedFileNames($filesByFile,$includedFileNames)
    {
        $return = [];

        foreach ($filesByFile as $key => $value) {
            if (in_array($key,$includedFileNames)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }
    /**
     * Exclude only specified names of files.
     *
     * @param array $filesByFile
     * @param array $includedFileNames 
     * @return array
     */
    public function filterByExcludedFileNames($filesByFile,$excludedFileNames)
    {
        $return = [];

        foreach ($filesByFile as $key => $value) {
            if (!in_array($key,$excludedFileNames)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }
    /**
     * Get all supported languages after exclude or include.
     *
     * @param array $filesByFile
     * 
     * @return array 
     */
    public function getSupportedLanguages($filesByFile)
    {
        $return = [];

        foreach ($filesByFile as $key => $nesValue) {
            foreach($nesValue as $key => $nesValue)
            {
                $return[$key] = 1;
            }
        }

        return array_keys($return);
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
            return $directory != 'vendor' && !in_array($directory, $this->exclude_extensions);
        });

        sort($languages);

        return Arr::except($languages, array_merge(['vendor',],$this->exclude_extensions));
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
            $file = $this->path."/{$languageKey}/{$fileName}.".$this->extension;
            if (! $this->disk->exists($file)) {
                if($this->extension == "json")
                    file_put_contents($file, "{ \n\n }");
                if($this->extension == "php")
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
                $filePath = $this->path."/{$languageKey}/{$fileName}.".$this->extension;

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
            $filePath = $this->path."/{$language}/{$fileName}.".$this->extension;

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
        switch ($this->extension) {
            case "json":
                $content = "{\n";

                $content .= $this->stringJsonLineMaker($translations);
        
                $content .= "\n}";
                break;
            case "php":
                $content = "<?php \n\nreturn [";

                $content .= $this->stringLineMaker($translations);
        
                $content .= "\n];";
                break;
            default:
                $content = "<?php \n\nreturn [";

                $content .= $this->stringLineMaker($translations);
        
                $content .= "\n];";
          }

        file_put_contents($filePath, $content);
    }
    
    /**
     * Write the lines of the inner array of the JSON language file.
     *
     * @param $array
     * @return string
     */
    private function stringJsonLineMaker($array, $prepend = '')
    {
        $output = '';

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->stringJsonLineMaker($value, $prepend.'    ');
                $output .= "\n".$prepend.'   "'.$key.'" : {"'.$value.'" "'.$prepend.'"    },';
            } else {
                $value = str_replace("\'", "'", addslashes($value));
                $key = str_replace("\'", "'", addslashes($key));
                $output .= "\n".$prepend.'   "'.$key.'" : "'.$value.'",';
            }
        }
        $output = substr($output,0,-1);
        // dd($output);
        return $output;
    }
    /**
     * Write the lines of the inner array of the PHP language file.
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
