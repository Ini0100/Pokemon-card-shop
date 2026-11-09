<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Config;
use App\Core\Container;
use App\Core\Database;
use App\Core\Env;
use App\Core\Session;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Services\Auth;
use App\Services\CartService;

const ROOT_PATH = __DIR__;

// Composer is optional: it only provides PHPMailer. Application classes are
// loaded by the PSR-4 autoloader below so the project runs on a bare XAMPP.
if (is_file(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = ROOT_PATH . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
});

require ROOT_PATH . '/src/helpers.php';

Env::load(ROOT_PATH . '/.env');
$config = new Config(require ROOT_PATH . '/config/app.php');

error_reporting(E_ALL);
ini_set('display_errors', $config->get('app.debug') ? '1' : '0');
date_default_timezone_set('Europe/Madrid');

$container = new Container();
$container->instance(Config::class, $config);
$container->set(PDO::class, static fn () => Database::connect($config));
$container->set(View::class, static fn () => new View(ROOT_PATH . '/views'));

// Command-line scripts (bin/) only need configuration and services.
if (PHP_SAPI === 'cli') {
    App::init($container, '');

    return $container;
}

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
App::init($container, $basePath === '.' ? '' : $basePath);

$container->get(Session::class)->start();

// Data available to every template (navigation, cart badge, current user…).
$container->get(View::class)->share(static function () use ($container): array {
    $auth = $container->get(Auth::class);
    $user = $auth->user();

    return [
        'appName'        => $container->get(Config::class)->get('app.name'),
        'currentUser'    => $user,
        'navCategories'  => $container->get(CategoryRepository::class)->all(),
        'cartCount'      => $user ? $container->get(CartService::class)->countItems((int) $user['id']) : 0,
        'flashes'        => $container->get(Session::class)->pullFlashes(),
    ];
});

return $container;
