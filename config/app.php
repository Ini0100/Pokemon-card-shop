<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'name'  => Env::get('APP_NAME', 'TCG Market'),
        'env'   => Env::get('APP_ENV', 'production'),
        'debug' => Env::bool('APP_DEBUG', false),
        'url'   => rtrim((string) Env::get('APP_URL', ''), '/'),
    ],

    'database' => [
        'host'     => Env::get('DB_HOST', '127.0.0.1'),
        'port'     => (int) Env::get('DB_PORT', '3306'),
        'name'     => Env::get('DB_DATABASE', 'tcg_market'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ],

    'cart' => [
        'reservation_minutes' => (int) Env::get('CART_RESERVATION_MINUTES', '60'),
    ],

    'uploads' => [
        'directory'     => dirname(__DIR__) . '/public/uploads',
        'public_prefix' => 'uploads',
        'max_bytes'     => 2 * 1024 * 1024,
    ],

    'mail' => [
        'host'       => Env::get('MAIL_HOST', ''),
        'port'       => (int) Env::get('MAIL_PORT', '587'),
        'username'   => Env::get('MAIL_USERNAME', ''),
        'password'   => Env::get('MAIL_PASSWORD', ''),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'),
        'from'       => [
            'address' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@tcgmarket.test'),
            'name'    => Env::get('MAIL_FROM_NAME', 'TCG Market'),
        ],
    ],
];
