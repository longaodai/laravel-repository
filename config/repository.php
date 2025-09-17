<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Composer Autoload Settings
    |--------------------------------------------------------------------------
    |
    | These options control whether "composer dump-autoload" should run
    | automatically after generating repositories or services.
    |
    | Purpose:
    | Running "composer dump-autoload" will refresh the Composer autoloader
    | so that Laravel can immediately recognize new bindings between
    | interfaces and their service/repository implementations.
    |
    | Logic:
    | - 'dump_auto_load' = true
    |       → Always run automatically after generation.
    | - 'dump_auto_load' = false + 'ask_dump_auto_load' = true
    |       → Ask for confirmation before running.
    | - 'dump_auto_load' = false + 'ask_dump_auto_load' = false
    |       → Never run (manual dump-autoload required).
    |
    */

    // Run "composer dump-autoload" automatically after generation
    'dump_auto_load' => true,

    // Ask for confirmation before running "composer dump-autoload"
    'ask_dump_auto_load' => false,

    /*
    |--------------------------------------------------------------------------
    | Pagination Limit
    |--------------------------------------------------------------------------
    |
    | Default number of records per page for repository pagination methods.
    | Default: 20 (can be overridden here).
    |
    */
    'limit_paginate' => 20,
];
