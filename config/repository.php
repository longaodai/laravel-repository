<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Repository Path
    |--------------------------------------------------------------------------
    |
    | Define the base path where repositories will be generated inside
    | your Laravel application. By default, repositories are placed under
    | "app/Repositories".
    |
    */
    'path_repository' => 'Repositories',

    /*
    |--------------------------------------------------------------------------
    | Service Path
    |--------------------------------------------------------------------------
    |
    | Define the base path where services will be generated inside
    | your Laravel application. By default, services are placed under
    | "app/Services".
    |
    */
    'path_service' => 'Services',

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
    'dump_auto_load' => false,

    // Ask for confirmation before running "composer dump-autoload"
    'ask_dump_auto_load' => true,

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
