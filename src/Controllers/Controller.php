<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function render(string $template, array $data = [], int $status = 200): void
    {
        Response::html(App::container()->get(View::class)->render($template, $data), $status);
    }

    /** @param array<string, mixed> $query */
    protected function redirect(string $path, array $query = []): never
    {
        Response::redirect(url($path, $query));
    }

    protected function flash(string $type, string $message): void
    {
        App::container()->get(Session::class)->flash($type, $message);
    }
}
