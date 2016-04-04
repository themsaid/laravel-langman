<h1 align="center">Laravel Langman</h1>

<p align="center">
Langman is a language files manager in your artisan console, it helps you search, update, add, and remove
translation lines with ease. Taking care of a multilingual interface is not a headache anymore.
<br>
<br>

<img src="http://s30.postimg.org/ni241hhpd/ezgif_com_resize.gif">

</p>

## Installation

Begin by installing the package through Composer. Run the following command in your terminal:

```
$ composer require themsaid/laravel-langman
```

Once done, add the following in the providers array of `config/app.php`:

```php
Themsaid\Langman\LangmanServiceProvider::class
```

This package has a single configuration option that points to the `resources/lang` directory, if only you need to change
the path then publish the config file:

```
php artisan vendor:publish --provider="Themsaid\Langman\LangmanServiceProvider"
```

## Usage

### Showing lines of a translation file

```
php artisan langman:show users
```

You get:

```
'+---------+---------------+-------------+
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

### Search view files for missing translations

```
php artisan langman:sync
```

This command will look into your view files and find all translation keys that are not covered in your translation files, after
that it appends those keys to the files with a value equal to an empty string.

### Fill missing translations

```
php artisan langman:missing
```

It'll collect all the keys that are missing in any of the languages or has values equals to an empty string, prompt
asking you to give a translation for each, and finally save the given values to the files.

### Translating a key

```
php artisan langman:trans users.name
php artisan langman:trans users.name.en
```

In the first case it'll ask you to give a translation for the given key in all languages, in the second case it'll ask you only
for the given language's value.

This command will add a new key if not existing, and updates the key if it is already there.

### Removing a key

```
php artisan langman:remove users.name
```

It'll remove that key from all language files.

## Notes

`langman:sync`, `langman:missing`, `langman:trans`, and `langman:remove` updates your language files by writing them completely, meaning that any comments or special styling will get
removed, so I recommend you backup your files.

## Web interface

If you want a web interface to manage your language files instead, I recommend [Laravel 5 Translation Manager](https://github.com/barryvdh/laravel-translation-manager)
by [Barry vd. Heuvel](https://github.com/barryvdh).