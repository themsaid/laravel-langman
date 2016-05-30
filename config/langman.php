<?php

return [
    /*
     * --------------------------------------------------------------------------
     * Path to the language directories
     * --------------------------------------------------------------------------
     *
     * This option determines the path to the languages directory, it's where
     * the package will be looking for translation files. These files are
     * usually located in resources/lang but you may change that.
     */

    'path' => realpath(base_path('resources/lang')),

    /*
     * --------------------------------------------------------------------------
     * Path to the directory where CSV files should be exported
     * --------------------------------------------------------------------------
     *
     * This option determines where to put the exported CSV files. This directory
     * must be writable by server. By default storage/langman-csv directory
     * will be used.
     */

    'csv_path' => storage_path('langman-csv'),
];
