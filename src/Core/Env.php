<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal `.env` loader. Supports `KEY=value`, quoted values and `#` comments.
 * Real environment variables always take precedence over the file.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            self::$values[$key] = self::parseValue($value);
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function parseValue(string $value): string
    {
        if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
            return $matches[2];
        }

        // Strip inline comments from unquoted values.
        return trim(preg_replace('/\s+#.*$/', '', $value));
    }
}
