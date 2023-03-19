<?php

declare(strict_types=1);

namespace SlimEdge\Support;

final class Paths
{
    /**
     * Application directory
     */
    public const App = BASEPATH . '/app';

    /**
     * Application configuration directory
     */
    public const Config = self::App . '/config';

    /**
     * Application dependencies directory
     */
    public const Dependencies = self::App . '/dependencies';

    /**
     * Application helpers directory
     */
    public const Helpers = self::App . '/helpers';

    /**
     * Application routes directory
     */
    public const Routes = self::App . '/routes';

    /**
     * Storage directory
     */
    public const Storage = BASEPATH . '/storage';

    /**
     * Cache directory
     */
    public const Cache = self::Storage . '/cache';

    /**
     * System directory
     */
    public const System = BASEPATH . '/system';
}