<?php

declare(strict_types=1);

/*
 * Front controller: every request that is not a static file is routed here.
 */

use App\Core\App;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;

// Let PHP's built-in server (`php -S`) serve static assets directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

$container = require dirname(__DIR__) . '/bootstrap.php';

$request = Request::fromGlobals(App::basePath());
$isApi   = str_starts_with($request->path, '/api/');

try {
    if ($request->isPost()) {
        $container->get(Csrf::class)->validate($request);
    }

    $router = new Router($container);
    (require dirname(__DIR__) . '/routes.php')($router);
    $router->dispatch($request);
} catch (Throwable $e) {
    $status  = $e instanceof HttpException ? $e->status : 500;
    $message = $e instanceof HttpException ? $e->getMessage() : 'Ha ocurrido un error inesperado.';

    if (!$e instanceof HttpException) {
        error_log((string) $e);

        if ($container->get(Config::class)->get('app.debug')) {
            $message .= ' ' . $e->getMessage();
        }
    }

    if ($isApi) {
        Response::json(['error' => $message], $status);
    } else {
        $html = $container->get(View::class)->render('errors/error', [
            'status'  => $status,
            'message' => $message,
        ]);
        Response::html($html, $status);
    }
}
