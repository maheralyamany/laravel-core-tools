<?php

declare(strict_types=1);

namespace Maher\CoreTools;

class CoreTools
{
     /**
     * Indicates if CoreTools's migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;



     /**
     * Determine if CoreTools's migrations should be run.
     *
     * @return bool
     */
    public static function shouldRunMigrations()
    {
        return static::$runsMigrations;
    }

    /**
     * Configure CoreTools to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }
}
