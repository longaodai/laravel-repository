<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Repository Path
    |--------------------------------------------------------------------------
    |
    | Define the base path where repositories will be generated inside your
    | Laravel application. By default, repositories will be placed under
    | the "app/Repositories" directory.
    |
    */

    'path_repository' => 'Repositories',

    /*
    |--------------------------------------------------------------------------
    | Service Path
    |--------------------------------------------------------------------------
    |
    | Define the base path where services will be generated inside your
    | Laravel application. By default, services will be placed under
    | the "app/Services" directory.
    |
    */

    'path_service' => 'Services',

    /**
     * Determines whether composer dump-autoload should run automatically.
     *
     * Logic:
     * - If `is_auto_load = true`  → always run (highest priority).
     * - Else if `is_ask_auto_load = true` → ask user confirmation.
     * - Else → do not run.
     *
     * Truth table:
     * | is_auto_load | is_ask_auto_load | confirm? | Result        |
     * |--------------|------------------|----------|---------------|
     * | true         | *                | *        | Run           |
     * | false        | true             | yes      | Run           |
     * | false        | true             | no       | Skip          |
     * | false        | false            | *        | Skip          |
     */
    /*
    |--------------------------------------------------------------------------
    | Auto Run Composer Dump-Autoload
    |--------------------------------------------------------------------------
    |
    | This option determines whether the package should automatically run
    | "composer dump-autoload" after generating repositories or services.
    |
    | - When set to true, the command will be executed silently without
    |   asking the user for confirmation.
    | - When set to false, it will either skip or prompt the user depending
    |   on the "is_ask_auto_load" setting below.
    |
    */

    'is_auto_load' => false,

    /*
    |--------------------------------------------------------------------------
    | Ask Before Running Composer Dump-Autoload
    |--------------------------------------------------------------------------
    |
    | This option specifies whether the user should be asked before running
    | "composer dump-autoload". It only applies when "is_auto_load" is false.
    |
    | - When set to true, the user will be prompted to confirm execution.
    | - When set to false, the package will skip running dump-autoload unless
    |   "is_auto_load" is explicitly enabled.
    |
    */

    'is_ask_auto_load' => true,
];
