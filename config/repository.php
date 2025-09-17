<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Composer Autoload Settings
    |--------------------------------------------------------------------------
    |
    | Control whether "composer dump-autoload" should run automatically
    | after generating repositories or services.
    |
    | Logic:
    | - 'dump_auto_load' = true  → always run automatically.
    | - 'dump_auto_load' = false + 'ask_dump_auto_load' = true → prompt user.
    | - 'dump_auto_load' = false + 'ask_dump_auto_load' = false → skip.
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
    | Base limit for paginated lists when using the repository methods.
    | Default: 20 records per page.
    |
    */
    'limit_paginate' => 20,
];
