# Laravel Langman

[![Latest Version](https://img.shields.io/github/release/oss-tools/laravel-langman.svg?style=flat-square)](https://github.com/oss-tools/laravel-langman/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/oss-tools/laravel-langman/run-tests?label=tests)
![Check & fix styling](https://github.com/oss-tools/laravel-langman/workflows/Check%20&%20fix%20styling/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/oss-tools/laravel-langman.svg?style=flat-square)](https://packagist.org/packages/oss-tools/laravel-langman)

### What is Langman?
Langman is a language files manager in your artisan console, it helps you search, update, add, and remove
translation lines with ease. Taking care of a multilingual interface is not a headache anymore.

## Note
This package is a detached fork of [themsaid/laravel-langman](https://github.com/themsaid/laravel-langman)

## Installation

Begin by installing the package through Composer. Run the following command in your terminal:

```
$ composer require oss-tools/laravel-langman
```

Once done, add the following line in your providers array of `config/app.php`:

```php
mrgegroup\Langman\LangmanServiceProvider::class
```

This package has a single configuration option that points to the `resources/lang` directory, if only you need to change
the path then publish the config file:

```
php artisan vendor:publish --provider="mrgegroup\Langman\LangmanServiceProvider"
```

## Usage

### Showing lines of a translation file

```
php artisan langman:show users
```

You get:

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

Brings only the translation of the `name` key in all languages.

---

```
php artisan langman:show users.name.first
```

Brings the translation of a nested key.

---

```
php artisan langman:show package::users.name
```

Brings the translation of a vendor package language file.

---

```
php artisan langman:show users --lang=en,it
```

Brings the translation of only the "en" and "it" languages.

---

```
php artisan langman:show users.nam -c
```

Brings only the translation lines with keys matching the given key via close match, so searching for `nam` brings values for
keys like (`name`, `username`, `branch_name_required`, etc...).

In the table returned by this command, if a translation is missing it'll be marked in red.

### Finding a translation line

```
php artisan langman:find 'log in first'
```

You get a table of language lines where any of the values matches the given phrase by close match.

### Searching view files for missing translations

```
php artisan langman:sync
```

This command will look into all files in `resources/views` and `app` and find all translation keys that are not covered in your translation files, after
that it appends those keys to the files with a value equal to an empty string.

### Filling missing translations

```
php artisan langman:missing
```

It'll collect all the keys that are missing in any of the languages or has values equals to an empty string, prompt
asking you to give a translation for each, and finally save the given values to the files.

### Translating a key

```
php artisan langman:trans users.name
php artisan langman:trans users.name.first
php artisan langman:trans users.name --lang=en
php artisan langman:trans package::users.name
```

Using this command you may set a language key (plain or nested) for a given group, you may also specify which language you wish to set leaving the other languages as is.

This command will add a new key if not existing, and updates the key if it is already there.

### Removing a key

```
php artisan langman:remove users.name
php artisan langman:remove package::users.name
```

It'll remove that key from all language files.

### Renaming a key

```
php artisan langman:rename users.name full_name
```

This will rename `users.name` to be `users.full_name`, the console will output a list of files where the key used to exist.

## Notes

`langman:sync`, `langman:missing`, `langman:trans`, and `langman:remove` will update your language files by writing them completely, meaning that any comments or special styling will be removed, so I recommend you backup your files.

## Web interface

If you want a web interface to manage your language files instead, I recommend [Laravel 5 Translation Manager](https://github.com/barryvdh/laravel-translation-manager)
by [Barry vd. Heuvel](https://github.com/barryvdh).

## Testing
```
composer test
```
