## v1.2.2 (2016-04-13)
- Sync command looks for translations in the app directory as well as the views directory
- Updated the lookup regex to match exact translation methods. e. g. `trans()` but not `othertrans()`
- The find command is now case insensitive.

## v1.2.1 (2016-04-09)
- Fix bug with creating vendor language files
- Add the ability to pick specific languages to show in `langman:show`

## v1.2.0 (2016-04-09)
- Fix bugs on windows os.
- Support nested language keys.
- Support vendor package translation lines.
- Enhance the output of commands to be more descriptive and (colorful).

## v1.1.2 (2016-04-04)
- Added support for nested keys in the `show` and `find` commands.
- Fixed an issue with writing language files with nested keys.

## v1.1.1 (2016-04-04)
- Support for PHP>=5.5.9
- Support for laravel 5.1

## v1.0 (2016-04-02)
- Initial release