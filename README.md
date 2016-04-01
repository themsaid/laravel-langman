# Laravel Langman

Langman is a language files manager in your artisan console, it helps you search, update, add, and remove
translation lines with ease. Taking care of a multilingual interface is not a headache anymore.

## Installation

You'll need PHP 7.0+ to run this tool.

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

```
php artisan langman:show users.name
```

Brings only the translation of the `name` key in all languages.

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

### Search for missing translations

```
php artisan langman:missing
```

It'll look into your language files, collect all the lines that has missing values in any of the languages, prompt
asking you to give a translation for each key, and finally save the given values to the files.

### Translating a key

```
php artisan langman:translate users.name
php artisan langman:translate users.name.en
```

In the first case it'll ask you to give a translation for the given key in all languages, in the second case it'll ask you only
for the given language's value.

This command will add a new key if not existing, and updates the key if it is already there.

### Removing a key

```
php artisan langman:remove users.name
```

It'll remove that key from all language files.