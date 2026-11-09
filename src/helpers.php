<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Config;
use App\Core\Csrf;

/** Escapes a value for safe output in HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @param array<string, mixed> $query */
function url(string $path = '/', array $query = []): string
{
    $url = App::basePath() . '/' . ltrim($path, '/');

    return $query === [] ? $url : $url . '?' . http_build_query($query);
}

/** Absolute URL, used where a full link is required (e.g. e-mails). */
function absolute_url(string $path = '/', array $query = []): string
{
    $base = App::container()->get(Config::class)->get('app.url');

    if ($base === '') {
        $scheme = ($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http';
        $base   = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . App::basePath();
    }

    $url = $base . '/' . ltrim($path, '/');

    return $query === [] ? $url : $url . '?' . http_build_query($query);
}

function asset(string $path): string
{
    return url($path);
}

/**
 * URL of a product image. `image_path` may be a local path under public/ or a
 * remote URL; TCGdex assets are requested in the given quality ('low' for
 * listings, 'high' for detail views) and are never stored in this project.
 */
function product_image(string $path, string $quality = 'low'): string
{
    if (str_starts_with($path, 'https://assets.tcgdex.net/')) {
        return $path . '/' . ($quality === 'high' ? 'high' : 'low') . '.webp';
    }

    return preg_match('#^https?://#', $path) ? $path : url($path);
}

function money(float|string $amount): string
{
    return number_format((float) $amount, 2, ',', '.') . ' €';
}

function csrf_field(): string
{
    $token = App::container()->get(Csrf::class)->token();

    return '<input type="hidden" name="' . Csrf::FIELD . '" value="' . e($token) . '">';
}

function config(string $key, mixed $default = null): mixed
{
    return App::container()->get(Config::class)->get($key, $default);
}
