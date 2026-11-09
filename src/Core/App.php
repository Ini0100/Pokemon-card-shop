<?php

declare(strict_types=1);

namespace App\Core;

use LogicException;

/** Holds the application-wide container and base URL path. */
final class App
{
    private static ?Container $container = null;
    private static string $basePath = '';

    public static function init(Container $container, string $basePath): void
    {
        self::$container = $container;
        self::$basePath  = rtrim($basePath, '/');
    }

    public static function container(): Container
    {
        return self::$container ?? throw new LogicException('Application has not been initialised.');
    }

    /** URL path prefix when the app is served from a subdirectory (e.g. `/tcg-market/public`). */
    public static function basePath(): string
    {
        return self::$basePath;
    }
}
