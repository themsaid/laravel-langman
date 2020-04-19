<h1 align="center">Laravel Langman</h1>

<p align="center">
Langman is a language files manager in your artisan console, it helps you search, update, add, and remove
translation lines with ease. Taking care of a multilingual interface is not a headache anymore.
<br>
<br>

<img src="http://s16.postimg.org/mghfe2v3p/ezgif_com_optimize.gif" alt="Laravel Langman">
<br>
<a href="https://travis-ci.org/themsaid/laravel-langman"><img src="https://travis-ci.org/themsaid/laravel-langman.svg?branch=master" alt="Build Status"></a>
<a href="https://styleci.io/repos/55088784"><img src="https://styleci.io/repos/55088784/shield?style=flat" alt="StyleCI"></a>
<a href="https://packagist.org/packages/themsaid/laravel-langman"><img src="https://poser.pugx.org/themsaid/laravel-langman/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/themsaid/laravel-langman"><img src="https://poser.pugx.org/themsaid/laravel-langman/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/themsaid/laravel-langman"><img src="https://poser.pugx.org/themsaid/laravel-langman/license.svg" alt="License"></a>

</p>

## Installation

Begin by installing the package through Composer. Run the following command in your terminal:

```
$ composer require themsaid/laravel-langman
```

Once done, check that the following line was added in your providers array of `config/app.php`:

```php
Themsaid\Langman\LangmanServiceProvider::class
```

This package has a single configuration option that points to the `resources/lang` directory. If you 
only need to change the path then publish the config file:

```
php artisan vendor:publish --provider="Themsaid\Langman\LangmanServiceProvider"
```

## Usage

### Showing lines of a translation file
```
php artisan langman:show [file.][key] [--close] [--unused] [--lang <language key(s)>]
```

In the table returned by the Show command, if a translation is missing it'll be marked in red.

```
php artisan langman:show
```
Shows all keys in the JSON translation located at `lang/<locale>.json`.

Example output:
```
+-----------------------------+---------------+-------------+
| key                         | en            | nl          |
+-----------------------------+---------------+-------------+
| What is in a name?          | MISSING       | MISSING     |
| Do you need more proof?     | MISSING       | MISSING     |
+-----------------------------+---------------+-------------+
```

```
php artisan langman:show users
```
Shows all keys in the `lang/<locale>/users.php` translation file. If no such file exists, langman
assumes that you are searching in the list of JSON strings.

Example output:

```
+---------+---------------+-------------+
| key     | en            | nl          |
+---------+---------------+-------------+
| name    | name          | naam        |
| job     | job           | baan        |
+---------+---------------+-------------+
```

---

```
php artisan langman:show users.name
```

Shows only the translation of the `name` key in all languages as found in the `lang/<locale>/users.php`
translation files.

---

```
php artisan langman:show users.name.first
```

Shows the translation of a nested key.

---

```
php artisan langman:show package::users.name
```

Shows the translation of a vendor package language file.

---

```
php artisan langman:show users --lang=en,it
```

Shows the translation of only the "en" and "it" languages.

---

```
php artisan langman:show users.nam -c
php artisan langman:show users.nam --close
```

Shows only the translation lines with keys containing the given key via substring match, so searching for 
`nam` brings values for keys like (`name`, `username`, `branch_name_required`, etc...).

If the close option is specified, the file/key option is always interpreted as a key if it does not 
contain a dot, or the resulting file does not exist. E.g., if the `lang/<locale>/users.php` file does not exist,
then:
```
php artisan langman:show users --close
```
would show all keys containing the 'users' string in the JSON translation files.

Similarly:
```
php artisan langman:show "I don't know. Perhaps" --close
```
will not look for a file called `"I don't know.php"`, but interpret the whole provided element as a key to search for.

---

```
php artisan langman:show users -u
php artisan langman:show users --unused
```

Scans all the view templates and application files (see Sync command) and outputs only the keys found in the
specified file that were not used in any template or file. This helps identifying legacy elements and keep
your translation files tight and orderly. 


### Finding a translation line

```
php artisan langman:find 'log in first'
```

You get a table of language lines where any of the values contains the given phrase. Strings from the JSON
translation files are capped at 40 characters to keep the output tidy.

### Searching view files for missing translations

```
php artisan langman:sync
```

This command will look into all files in `resources/views` and `app` and find all translation keys that are not 
covered in your translation files. After that it appends those keys to the files with an empty value. Then it 
synchronises all translation files and adds all missing entries in each file, ensuring that all translation files
for all locales contain an entry for the same set of keys.

### Filling missing translations

```
php artisan langman:missing
```

This command collects all the keys that are missing or empty in any of the languages, prompt you for a 
translation for each and finally saves the given values to the proper files.

### Translating a key

```
php artisan langman:trans users.name
php artisan langman:trans users.name.first
php artisan langman:trans users.name --lang=en
php artisan langman:trans package::users.name
php artisan langman:trans 'Translate Me'
```

Using this command you may set a language key (plain or nested) for a given group. You may also specify 
which language you wish to set, leaving the other languages as is.

This command will add a new key if it did not exist yet and updates the key if it is already there.

### Removing a key

```
php artisan langman:remove users.name
php artisan langman:remove package::users.name
php artisan langman:remove 'Random JSON string'
```

Removes the specified key from all relevant language files.

### Renaming a key

```
php artisan langman:rename users.name full_name
```
This will rename `users.name` to `users.full_name`. The console will output a list of files where 
the key used to exist.

```
php artisan langman:rename 'Json Search String' 'New Json Search String'
```
This will rename the JSON translatable string `'Json Search String'` to `'New Json Search String'` in all
relevant JSON translation files.

## Notes

`langman:sync`, `langman:missing`, `langman:trans`, and `langman:remove` will update your language files 
by rewriting them completely, meaning that any comments or special styling will be removed. I recommend 
that you backup your files if this is the first time you are running the tool. Langman sorts all keys in
files alphabetically by key name.

## Web interface

If you want a web interface to manage your language files instead, I recommend 
[Laravel Translation Manager](https://github.com/barryvdh/laravel-translation-manager) by [Barry vd. Heuvel](https://github.com/barryvdh).
